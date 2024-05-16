<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AgentHubApiService
{
    protected $baseUrl = env("URL_HUB");

    public function __construct(protected string $username, protected string $password)
    {}

    public function getUsers($page = 1, $limit = 10)
    {
        return $this->makeApiRequest('get', 'users/', [
            'page' => $page,
            'per_page' => $limit
        ]);
    }

    public function createUser(
        string $username,
        string $password,
        string $name = '',
        string $surname = '',
        string $email = '',
        bool $isAdmin = false,
        string|null $avatar = null,
        string|null $description = null,
        bool $isOperator = false,
    ) {
        return $this->makeApiRequest('post', 'users/', [
            'username' => $username,
            'password' => $password,
            'name' => $name,
            'surname' => $surname,
            'email' => $email,
            'is_admin' => $isAdmin,
            'avatar' => $avatar,
            'description' => $description,
            'is_operator' => $isOperator,
        ]);
    }

    public function showUser(string $id)
    {
        return $this->makeApiRequest('get', "users/{$id}",);
    }

    public function deleteUser(string $id)
    {
        return $this->makeApiRequest('delete', "users/{$id}",);
    }

    public function meUser()
    {
        return $this->makeApiRequest('get', "users/me/");
    }

    public function sendMessage(string $chatId, $content, $callbackUrl = null, $author = "user")
    {
        return $this->makeApiRequest('post', "chats/{$chatId}/message", [
            'id' => 1,
            'content' => $content,
            'author' => $author,
            'callback_url' => $callbackUrl,
            'chat_id' => $chatId,
            'detected_tags' => [],
        ]);
    }

    public function getGroups($page=1, $limit=10)
    {
        return $this->makeApiRequest('get', 'groups/', [
            'page' => $page,
            'per_page' => $limit
        ]);
    }

    public function createGroup(
        string $name, 
        string $description, 
        string $prompt = '', 
        array $availableTags = [],
        array $variables = []
    ) {
        return $this->makeApiRequest('post', 'groups/', [
            'name' => $name,
            'description' => $description,
            'raw_prompt' => $prompt,
            'available_tags' => $availableTags,
            'variables' => $variables,
        ]);
    }

    public function showGroup(string $id)
    {
        return $this->makeApiRequest('get', "groups/{$id}",);
    }

    public function deleteGroup(string $id)
    {
        return $this->makeApiRequest('delete', "groups/{$id}",);
    }

    public function getChats($page=1, $limit=10, $filter="")
    {
        return $this->makeApiRequest('get', "chats/", [
            'filter' => $filter,
            'page' => $page,
            'per_page' => $limit,
        ]);
    }

    public function meChats($page=1, $limit=10)
    {
        return $this->getChats($page, $limit, $filter="me");
    }

    public function createChat(
        string $name, 
        string|int $groupId,
        string|int $ownerId,
        bool $shareForAllUsers = false, 
        bool $aiEnabled = true,
    ) {
        return $this->makeApiRequest('post', 'chats/', [
            "name" => $name,
            "share_for_all_users" => $shareForAllUsers,
            "ai_enabled" => $aiEnabled,
            "group_id" => $groupId,
            "owner_id" => $ownerId
        ]);
    }
    
    public function authenticateAndCacheToken($username, $password)
    {
        $response = Http::post("{$this->baseUrl}/auth/login/", [
            'login' => $username,
            'password' => $password,
            'remember_me' => true
        ]);

        if ($response->successful()) {
            $accessToken = $response->json()['access_token'];
            [$scheme, $token] = explode(' ', $accessToken);
            // Assume token expiry time is 1 hour; adjust if API specifies otherwise
            $expiresIn = $response->json()['expires_in'] ?? 3600; 

            // Store the token in the cache for 5 minutes less than its actual expiry time
            Cache::put("agent_hub_api_token_{$username}", $token, now()->addSeconds($expiresIn - 300));

            return $token;
        }

        throw new \Exception("Unable to authenticate with the API: " . $response->body());
    }

    public function getToken($username, $password)
    {
        return Cache::get(
            "agent_hub_api_token_{$username}", 
            $this->authenticateAndCacheToken($username, $password)
        );
    }

    public function makeApiRequest(
        string $method, 
        string $endpoint, 
        array $data = [], 
        string $username = null, 
        string $password = null
    )
    {
        $username = $username ?: $this->username;
        $password = $password ?: $this->password;
        // Попытаться выполнить запрос с существующим токеном
        $token = $this->getToken($username, $password);

        $response = Http::withToken($token)->$method("/{$this->baseUrl}/{$endpoint}", $data);

        // Если токен истек, попытаться его обновить и повторить запрос
        if ($response->status() === 401) {
            // Здесь вызывается тот же метод, что и для первоначальной аутентификации
            $token = $this->authenticateAndCacheToken($username, $password);

            // Повторить запрос с новым токеном
            $response = Http::withToken($token)->$method("{$this->baseUrl}{$endpoint}", $data);
        }

        return $response;    
    }

    public function registerUser($userData)
    {
        $response = Http::post("{$this->baseUrl}/users/", [
            'username' => $userData['username'],
            'name' => $userData['name'],
            'surname' => $userData['surname'],
            'email' => $userData['email'],
            'is_admin' => $userData['is_admin'],
            'avatar' => $userData['avatar'],
            'description' => $userData['description'],
            'is_operator' => $userData['is_operator'],
            'password' => $userData['password']
        ]);

        if ($response->successful()) {
            return $response->json();
        } else {
            // Обработка ошибок
            throw new \Exception("Failed to register user: " . $response->body());
        }
    }
}
