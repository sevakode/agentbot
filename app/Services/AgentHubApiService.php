<?

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AgentHubApiService
{
    protected $baseUrl = 'https://apiagenthubapi.ascender.space';
    public function authenticateAndCacheToken($username, $password)
    {
        $response = Http::post("{$this->baseUrl}/auth/token", [
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->successful()) {
            $token = $response->json()['access_token'];
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
        $response = Http::post("{$this->baseUrl}/auth/token", [
            'username' => $username,
            'password' => $password,
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        } else if ($response->status() === 401) {
            // Обновление токена, если необходимо
        }

        // Обработка ошибок
    }

    // app/Services/AgentHubApiService.php

// ... 

public function makeApiRequest($method, $endpoint, $data, $username, $password)
{
    // Попытаться выполнить запрос с существующим токеном
    $token = $this->getToken($username, $password);

    $response = Http::withToken($token)->$method("{$this->baseUrl}{$endpoint}", $data);

    // Если токен истек, попытаться его обновить и повторить запрос
    if ($response->status() === 401) {
        // Здесь вызывается тот же метод, что и для первоначальной аутентификации
        $token = $this->authenticateAndCacheToken($username, $password);

        // Повторить запрос с новым токеном
        $response = Http::withToken($token)->$method("{$this->baseUrl}{$endpoint}", $data);
    }

    if ($response->successful()) {
        return $response->json();
    } else {
        // Обработка других ошибок
        throw new \Exception("API request failed: " . $response->body());
    }
    
}
// app/Services/AgentHubApiService.php

// ... 

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

// ...
    

// ...

    // Дополнительные методы для регистрации, отправки сообщения, создания группы и т.д.
}
