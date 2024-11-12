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

    public function checkWebhook($payload)
    {
        $action = $payload['action'];
        if ($action['type'] === 'updateCard') {
            $cardName = $action['data']['card']['name'];
            $fromList = $action['data']['listBefore']['name'];
            $toList = $action['data']['listAfter']['name'];

            if ($fromList === 'todo' || $toList === 'todo') {
                return false;
            }

            return [
                'cardName' => $cardName,
                'fromList' => $fromList,
                'toList' => $toList
            ];
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