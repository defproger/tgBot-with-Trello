<?php

use app\Bot;

include 'app/error_log.php';
require_once 'app/inc.php';

$bot = new Bot();
$bot->startLog();
$bot->goingChecker = true;

$trello = new Trello($_CONFIG['trelloKey'], $_CONFIG['trelloSecret'], $_CONFIG['boardId']);
print_r($trello->createWebhook("https://test123777.theweb.place/trello.php"));


$bot->log('trello', file_get_contents('php://input'));
$bot->log('trello', $_POST);
$bot->log('trello', $_GET);
$bot->log('trello', $_REQUEST);
$data = json_decode(file_get_contents('php://input'), true);


$bot->log('trello', $data);

if (!empty($data)) {
    [$cardName, $from, $to] = $trello->checkWebhook($data);
    $bot->log('trello', "$cardName | $from | $to");
} else {
    $bot->log('trello', 'Empty payload received');
}