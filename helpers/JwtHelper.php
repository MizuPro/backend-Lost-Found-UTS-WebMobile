<?php

/**
 * JwtHelper - implementasi JWT manual (tanpa library eksternal)
 * Menggunakan HMAC SHA-256
 */
class JwtHelper
{
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Buat JWT token
     */
    public static function encode(array $payload): string
    {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRE;

        $encodedPayload = self::base64UrlEncode(json_encode($payload));
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', $header . '.' . $encodedPayload, JWT_SECRET, true)
        );

        return $header . '.' . $encodedPayload . '.' . $signature;
    }

    /**
     * Decode & validasi JWT token
     * Return: array payload atau null jika tidak valid
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $header    = $parts[0];
        $payload   = $parts[1];
        $signature = $parts[2];

        // Verifikasi signature
        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', $header . '.' . $payload, JWT_SECRET, true)
        );

        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }

        $decodedPayload = json_decode(self::base64UrlDecode($payload), true);

        if (!$decodedPayload) {
            return null;
        }

        // Cek expiry
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            return null; // Token sudah kadaluarsa
        }

        return $decodedPayload;
    }

    /**
     * Ambil token dari header Authorization: Bearer <token>
     */
    public static function getBearerToken(): ?string
    {
        $headers = null;

        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }

        if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
