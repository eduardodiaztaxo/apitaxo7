<?php

namespace App\Services;

class TokenEncodeDecodeService
{
    private $seed;

    public function __construct($seed = null)
    {
        $this->seed = $seed;
    }


    public function encode(array $data): string
    {
        $iv = random_bytes(16); // Initialization vector 
        $payload = json_encode($data);
        $encrypted = openssl_encrypt($payload, 'AES-128-CBC', $this->seed, 0, $iv); //antes 32 bytes AES-256 y la cambie a 16 bytes AES-128
        //para que no de este error: openssl_decrypt(): IV passed is only 9 bytes long, cipher expects an IV of precisely 16 bytes
        return base64_encode($iv . $encrypted);
    }

    public function decode(string $token): ?array
    {
        $decoded = base64_decode($token, true);

        if ($decoded === false || strlen($decoded) < 17) {
            return null; // token inválido
        }

        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        $decrypted = openssl_decrypt($encrypted, 'AES-128-CBC', $this->seed, 0, $iv); //antes AES-256

        return $decrypted !== false ? json_decode($decrypted, true) : null;
    }

    //Test seed
    public function getSeed(): string
    {
        return $this->seed;
    }
}
