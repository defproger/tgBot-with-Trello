<?php

use app\Bot;

include 'app/error_log.php';
require_once 'app/inc.php';

$bot = new Bot();
$bot->startLog();
$bot->goingChecker = true;

$trello = new Trello($_CONFIG['trelloKey'], $_CONFIG['trelloSecret'], $_CONFIG['boardId']);
//print_r($trello->createWebhook("https://test123777.theweb.place/trello.php"));

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data)) {
    [$cardName, $from, $to] = $trello->checkWebhook($data);
    if (!empty($cardName) && !empty($from) && !empty($to)) {
        $bot->log('trello', "$cardName | $from | $to");
        $chats = db_getAll("chats");
        foreach ($chats as $chat) {
            $bot->toChat($chat['chat_id'])->Message("Карточка $cardName перемещена из списка $from в список $to")->Send();
        }
    }
}