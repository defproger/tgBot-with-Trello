<?php

namespace app;
use AllowDynamicProperties;

require_once 'Msg.php';

#[AllowDynamicProperties] class Bot
{

    private $token;
    private $botApi;
    private $update;
    //not deleted
    public $chatId = null;
    public $user;
    public $member; // for new chat members
    public $lastSendMessage = null;
    public $inputMessage = null;
    public $contact = null;
    protected $message_id = null;
    protected $logging = false;
    //deleted
    public $toChatId = null;
    protected $goingChecker = null;
    protected $fromFunctionChecker = null;
    protected $text = null;
    protected $video = null;
    protected $parse_mode = null;
    protected $method = null;
    protected $replyMarkupBody = null;


    public function __construct($token = null, $polling = false)
    {
        global $_CONFIG;
        $this->token = $token ?? $_CONFIG['token'];
        $this->botApi = "https://api.telegram.org/bot{$this->token}/";

        $this->log('bot', 'started');
        $this->update = json_decode(file_get_contents('php://input'), TRUE);
        $this->setUpdateValues();
        $this->log('update', $this->update);
    }

    private function setUpdateValues()
    {
        if ($this->update['message']) {
            $this->chatId = $this->update['message']['chat']['id'];
            $this->userId = $this->update['message']['from']['id'];
            $this->user = new \stdClass();
            $this->user->id = $this->update['message']['from']['id'];
            $this->user->username = $this->update['message']['from']['username'];
            $this->user->first_name = $this->update['message']['from']['first_name'];
            $this->user->last_name = $this->update['message']['from']['last_name'];
            $this->user->is_bot = $this->update['message']['from']['is_bot'];
            $this->message_id = $this->update['message']['message_id'];
            $this->inputMessage = $this->update['message']['text'];

        } elseif ($this->update['callback_query']['data']) {
            $this->chatId = $this->update['callback_query']['message']['chat']['id'];
            $this->userId = $this->update['callback_query']['from']['id'];
            $this->user = new \stdClass();
            $this->user->id = $this->update['callback_query']['from']['id'];
            $this->user->username = $this->update['callback_query']['from']['username'];
            $this->user->first_name = $this->update['callback_query']['from']['first_name'];
            $this->user->last_name = $this->update['callback_query']['from']['last_name'];
            $this->user->is_bot = $this->update['message']['from']['is_bot'];
            $this->message_id = $this->update['callback_query']['message']['message_id'];
//            $this->inputMessage = $this->update['callback_query']['message']['text'];
        }
        if ($this->update['message']['contact'])
            $this->contact = $this->update['message']['contact']['phone_number'];
        if ($this->update['message']['new_chat_members'] || $this->update['message']['left_chat_member']) {
            $this->member = new \stdClass();
            $this->member->id = $this->update['message']['new_chat_member']['id'];
            $this->member->first_name = $this->update['message']['new_chat_member']['first_name'];
            $this->member->last_name = $this->update['message']['new_chat_member']['last_name'];
            $this->member->username = $this->update['message']['new_chat_member']['username'];
            $this->member->is_bot = $this->update['message']['new_chat_member']['is_bot'];
        }

    }


    public function SetWebhook($domain = null)
    {
        $domain = $domain ?? "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
        file_get_contents($this->botApi . "setWebhook?url={$domain}");
    }

    public function DeleteWebhook($dropupdates = false)
    {
        file_get_contents($this->botApi . "deleteWebhook" . ($dropupdates ? '?drop_pending_updates=true' : ''));
    }

    //Отсыл
    public function toChat($chatId)
    {
        $this->toChatId = $chatId;
        return $this;
    }

    public function Message($text, $parse = null)
    {
        $this->text = $text;
        $this->parse_mode = $parse;
        return $this;
    }

    public function replyMarkup($inlineOrKeyboard)
    {
        //todo автоматическое распределение
        if ($this->goingChecker) self::log('replyMarkup()', print_r($inlineOrKeyboard, 1));

        $this->replyMarkupBody = "&reply_markup=" . json_encode($inlineOrKeyboard);
        return $this;
    }

    public function removeKeyboard()
    {
        $this->replyMarkupBody = "&reply_markup=" . json_encode(['remove_keyboard' => true]);
        return $this;
    }

    public function Video($file_id)
    {
        $this->method = 'sendVideo';
        $this->video = $file_id;

        return $this;
    }

    public function Edit($msg = null)
    {
        $this->method = 'editMessageText';
        if ($msg !== null)
            $this->message_id = $msg;

        return $this;
    }

    public function Send()
    {
        if (empty($this->update) && !empty($this->toChatId)) $this->goingChecker = true;

        $chat_id = $this->toChatId === null ? $this->chatId : $this->toChatId;

        $headers = [
            'chat_id' => $chat_id,
            'parse_mode' => $this->parse_mode
        ];


        if ($this->method === null)
            $this->method = 'sendMessage';
        elseif ($this->method === 'editMessageText')
            $headers['message_id'] = $this->message_id;


        if ($this->video !== null) {
            $headers['video'] = $this->video;
            $headers['caption'] = $this->text;
        } else {
            if ($this->method === 'editMessageText' && $this->text === null) $this->method = 'editMessageReplyMarkup';
            else $headers['text'] = $this->text;
        }

        $sendHead = http_build_query($headers);

        if ($chat_id !== null && $this->method !== null && $this->goingChecker) {
            $r = $this->botApi . "{$this->method}?{$sendHead}{$this->replyMarkupBody}";
            self::log('Send()', 'Запрос ' . $r);
            $this->lastSendMessage = json_decode(file_get_contents($r), 1);
            if ($this->lastSendMessage['ok']) $this->lastSendMessage = $this->lastSendMessage['result'];
            else self::log('Send() ERROR', $this->lastSendMessage);
        } elseif ($this->goingChecker) {
            self::log('Send() IF ERROR', "\nHeaders: " . print_r($headers, 1) . "\n Method: {$this->method}\n Checker: true");
        }
        $this->toChatId = null;
        $this->parse_mode = null;
        $this->text = null;
        $this->video = null;
        $this->method = null;
        $this->replyMarkupBody = null;

        if (!$this->fromFunctionChecker) $this->goingChecker = null;

    }

    public function delete($id = null)
    {
        $sendHead = http_build_query([
            'chat_id' => $this->toChatId === null ? $this->chatId : $this->toChatId,
            'message_id' => $id === null ? $this->message_id : $id
        ]);

        file_get_contents($this->botApi . "deleteMessage?{$sendHead})");
    }

    public function pin($id = null)
    {
        $sendHead = http_build_query([
            'chat_id' => $this->toChatId === null ? $this->chatId : $this->toChatId,
            'message_id' => $id === null ? $this->lastSendMessage['message_id'] : $id
        ]);

        file_get_contents($this->botApi . "pinChatMessage?{$sendHead})");
    }

    public function unpin($id = null)
    {
        $sendHead = http_build_query([
            'chat_id' => $this->toChatId === null ? $this->chatId : $this->toChatId,
            'message_id' => $id === null ? $this->lastSendMessage['message_id'] : $id
        ]);

        file_get_contents($this->botApi . "unpinChatMessage?{$sendHead})");
    }

    //Получение

    public function data($data, $f = null)
    {
        $this->goingChecker = ($data === '*' && isset($this->update['callback_query']['data'])) || $data === $this->update['callback_query']['data'];
        if ($f !== null && $this->goingChecker) {
            $this->fromFunctionChecker = true;
            $f($this, $this->update['callback_query']['data'], $this->update['callback_query']['message']['text']);
            $this->goingChecker = null;
        }
        return $this;
    }

    public function text($text, $f = null)
    {
        $this->goingChecker = ($text === '*' && isset($this->update['message'])) || $text === $this->update['message']['text'];
        if ($f !== null && $this->goingChecker) {
            $this->fromFunctionChecker = true;
            $f($this, $this->inputMessage);
            $this->goingChecker = null;
        }
        return $this;
    }

    public function grepData($regex, $f = null)
    {
        if ($f !== null && preg_match($regex, $this->update['callback_query']['data'], $matches)) {
            $this->goingChecker = true;
            $this->fromFunctionChecker = true;
            $f($this, $matches, $this->update['callback_query']['data']);
            $this->goingChecker = null;
        }
    }

    public function grepText($regex, $f = null)
    {

        if ($f !== null && preg_match($regex, $this->inputMessage, $matches)) {
            $this->goingChecker = true;
            $this->fromFunctionChecker = true;
            $f($this, $matches, $this->inputMessage);
            $this->goingChecker = null;
        }


        return $this;
    }

    public function getVideo($f = null)
    {
        $this->goingChecker = isset($this->update['message']['video']);
        if ($f !== null && $this->goingChecker) $f($this, $this->update['message']['video']['file_id']);
        else $this->goingChecker = null;
        return $this;
    }

    //работа с чатами
    public function isGroup()
    {
        return $this->chatId !== $this->user->id;
    }

    public function isBot()
    {
        return ($this->user->is_bot || $this->member->is_bot);
    }

    public function newMember($f = null)
    {
        $this->goingChecker = isset($this->update['message']['new_chat_members']);
        if ($f !== null && $this->goingChecker) {
            $this->fromFunctionChecker = true;
            $f($this);
            $this->goingChecker = null;
        }
        return $this;
    }

    public function leftMember($f = null)
    {
        $this->goingChecker = isset($this->update['message']['left_chat_member']);
        if ($f !== null && $this->goingChecker) {
            $this->fromFunctionChecker = true;
            $f($this);
            $this->goingChecker = null;
        }
        return $this;
    }

    //todo deletedMember

    public function startLog($fileName = null)
    {
        $f = $fileName === null ? '.log' : $fileName;
        $this->logging = true;
        $this->logfile = fopen($f, 'a+');

        self::log('-----------------------------------', '-----------------------------------');
        self::log('getUpdate', print_r($this->update, 1));
    }

    public function log($type, $text)
    {
        if ($this->logging) {
            if (is_array($text)) {
                $text = print_r($text, 1);
            }
            fputs($this->logfile, date('Y-m-d H:i:s') . " | {$type} | {$text}" . "\n");
        }
    }
}
