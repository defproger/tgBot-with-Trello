<?php
require_once 'app/inc.php';
echo "<pre>";

$trello = new Trello($_CONFIG['trelloKey'], $_CONFIG['trelloSecret'], $_CONFIG['boardId']);
print_r($trello->addList('todo'));
print_r($trello->addList('inProgress'));
print_r($trello->addList('Done'));

