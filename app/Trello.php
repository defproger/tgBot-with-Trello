<?php

class Trello
{
    private $apiKey;
    private $token;
    private $baseUrl = 'https://api.trello.com/1';

    private $boardId;

    public function __construct($apiKey, $token, $boardId)
    {
        $this->apiKey = $apiKey;
        $this->token = $token;
        $this->boardId = $boardId;
    }

    public function addList($name)
    {
        $url = "{$this->baseUrl}/boards/{$this->boardId}/lists";
        $data = [
            'name' => $name,
            'key' => $this->apiKey,
            'token' => $this->token
        ];

        return $this->makeRequest('POST', $url, $data);
    }

    public function getCards($listId)
    {
        $url = "{$this->baseUrl}/lists/{$listId}/cards";
        $data = [
            'key' => $this->apiKey,
            'token' => $this->token
        ];

        return $this->makeRequest('GET', $url, $data);
    }


    public function createWebhook($callbackUrl)
    {
        $url = "{$this->baseUrl}/webhooks?key=$this->apiKey&token=$this->token";

        $data = [
            'description' => 'Webhook for Trello board',
            'callbackURL' => $callbackUrl,
            'idModel' => $this->boardId
        ];

        return $this->makeRequest('POST', $url, $data);
    }


    public function checkWebhook($payload)
    {
        if (isset($payload['action']) && $payload['action']['type'] === 'updateCard') {
            $action = $payload['action'];

            $cardName = $action['data']['card']['name'] ?? null;
            $fromList = $action['data']['listBefore']['name'] ?? null;
            $toList = $action['data']['listAfter']['name'] ?? null;

            if (!$cardName || !$fromList || !$toList) {
                return false;
            }

            if ($fromList === 'todo' || $toList === 'todo') {
                return false;
            }

            return [$cardName, $fromList, $toList];
        }
        return false;
    }

    public function getCardMembers($cardId)
    {
        $url = "{$this->baseUrl}/cards/{$cardId}";
        $data = [
            'key' => $this->apiKey,
            'token' => $this->token,
            'fields' => 'name',
            'members' => 'true'
        ];

        $response = $this->makeRequest('GET', $url, $data);
        if (isset($response['members'])) {
            $tasks = [];
            foreach ($response['members'] as $member) {
                $tasks[] = [
                    'name' => $response['name'],
                    'responsible' => $member['fullName']
                ];
            }
            return $tasks;
        }
        return [];
    }

    public function getMembers()
    {
        $url = "{$this->baseUrl}/boards/{$this->boardId}/members";
        $data = [
            'key' => $this->apiKey,
            'token' => $this->token
        ];

        $response = $this->makeRequest('GET', $url, $data);

        if (is_array($response)) {
            $members = [];
            foreach ($response as $member) {
                $members[] = [
                    'id' => $member['id'],
                    'username' => $member['username'],
                    'fullName' => $member['fullName']
                ];
            }
            return $members;
        }

        return false;
    }

    private function makeRequest($method, $url, $data = [])
    {
        $ch = curl_init();

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}