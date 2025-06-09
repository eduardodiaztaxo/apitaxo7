<?php

namespace App\Services;

class TokenManager
{
    private $seed;

    public function __construct($seed)
    {
        $this->seed = $seed;
    }

    public function encode(array $data): string
    {
        $iv = random_bytes(16); // Initialization vector for encryption
        $payload = json_encode($data);
        $encrypted = openssl_encrypt($payload, 'AES-256-CBC', $this->seed, 0, $iv);
        return base64_encode($iv . $encrypted); // Combine IV with encrypted data
    }

    public function decode(string $token): ?array
    {
        $decoded = base64_decode($token);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->seed, 0, $iv);

        return $decrypted !== false ? json_decode($decrypted, true) : null;
    }
}
