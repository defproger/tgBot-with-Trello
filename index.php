<?php

use app\Bot;
use app\Msg;

include_once "app/error_log.php";
require_once "app/inc.php";

$bot = new Bot();
$bot->startLog();
//$bot->SetWebhook();
if (!$bot->isGroup() || $bot->isBot()) exit();
$chat = db_getById("chats", $bot->chatId, 'chat_id');
if (!empty($chat)) {
    $user = query("select * from usersInChats where uid={$bot->user->id} and chat={$chat['id']}")[0];
} else {
    db_insert("chats", [
        'chat_id' => $bot->chatId,
    ]);
    $chat = db_lastId();
}

$bot->text('/start', function (Bot $b) use ($chat, $user) {
    if (empty($user)) {
        db_insert("usersInChats", [
            'uid' => $b->user->id,
            'chat' => $chat['id'],
            'name' => $b->user->first_name . " " . $b->user->last_name,
        ]);
    }
    $b->Message("Доброго времени суток [{$b->user->first_name} {$b->user->last_name}](tg://user?id={$b->user->id})\!", "MarkdownV2")->Send();
});

$bot->text('/addTrello', function (Bot $b) use ($chat, $user, $_CONFIG) {
    if (empty($user)) {
        exit();
    }

    $trello = new Trello($_CONFIG['trelloKey'], $_CONFIG['trelloSecret'], $_CONFIG['boardId']);
    $list = $trello->getMembers();

    if (!$list) {
        $b->Message('Error with user list')->Send();
        exit();
    }

    $markup = [];

    foreach ($list as $item) {
        $markup[] = Msg::dataBtn($item['fullName'], "addTrello_{$item['id']}_{$user['id']}");
    }

    $b->Message("Выберите себя")
        ->replyMarkup(Msg::inline(
            Msg::Row($markup)
        ))
        ->Send();
});

$bot->grepData('/^addTrello_(?P<itemId>\w+)_(?P<userId>\d+)$/', function (Bot $b, $data) use ($chat, $user) {
    db_update("usersInChats", $data['userId'], [
        'trello_id' => $data['itemId'],
    ]);
    $b->Message("Вы добавлены в Trello")->Send();
});

$bot->text('/report', function (Bot $b) use ($chat, $user, $_CONFIG) {
    if (empty($user)) {
        exit();
    }

    $trello = new Trello($_CONFIG['trelloKey'], $_CONFIG['trelloSecret'], $_CONFIG['boardId']);


    $lists = $trello->getLists();
    if (empty($lists)) {
        $b->Message('Нет списков')->Send();
        exit();
    }

    $inProgressList = $lists[array_search('inProgress', array_column($lists, 'name'))];
    if (!$inProgressList) {
        $b->Message('Колонка inProgress не найдена')->Send();
        exit();
    }

    $cards = $trello->getCards($inProgressList['id']);

    if (empty($cards)) {
        $b->Message('Нет задач в колонке inProgress')->Send();
        exit();
    }

    $users = query("select * from usersInChats where chat = {$chat['id']} and trello_id is not null");

    $report = array_map(function ($user) use ($cards) {
        $userCards = array_filter($cards, function ($card) use ($user) {
            return isset($card['idMembers']) && in_array($user['trello_id'], $card['idMembers']);
        });

        return [$user['id'] => $userCards];
    }, $users);

    $report = array_merge(...$report);

    $reportText = "*Отчёт по задачам в колонке inProgress:*\n\n";

    foreach ($users as $user) {
        $userId = $user['id'];
        $userName = $user['name'];
        $userLink = "[{$userName}](tg://user?id={$userId})";

        $taskCount = isset($report[$userId]) ? count($report[$userId]) : 0;
        $reportText .= "{$userLink} - {$taskCount} задач(и)\n";
    }

    $reportText .= "\n\n*Детальная информация:*\n";

    foreach ($users as $user) {
        $userId = $user['id'];
        $userName = $user['name'];
        $userLink = "[{$userName}](tg://user?id={$userId})";

        $reportText .= "\n{$userLink}:\n";

        if (isset($report[$userId]) && !empty($report[$userId])) {
            foreach ($report[$userId] as $task) {
                $reportText .= "• " . $task['name'] . "\n";
            }
        } else {
            $reportText .= "Нет задач\n";
        }
        $reportText .= "--------------------\n";
    }

    $b->Message($reportText, "Markdown")->Send();
});