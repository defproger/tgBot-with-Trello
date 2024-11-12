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
    $user = query("select * from usersInChats where uid={$bot->user->id} and chat={$chat['id']}");
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

$bot->text('/addTrello', function (Bot $b) use ($chat, $user) {
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
        $markup[] = Msg::dataBtn($item['fullName'], "addTrello_{$item['id']}_{$chat['id']}_{$user['id']}");
    }

    $b->Message("Выберите себя")
        ->replyMarkup(Msg::inline(
            Msg::Row($markup)
        ))
        ->Send();
});

