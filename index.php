<?php

use app\Bot;

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
