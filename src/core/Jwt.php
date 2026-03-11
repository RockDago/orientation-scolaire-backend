<?php

class Jwt {
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode(array $payload, string $secret): string {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }

    public static function decode(string $jwt, string $secret): array {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw new Exception('Token invalide');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $expected = self::base64UrlEncode(
            hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, $secret, true)
        );

        if (!hash_equals($expected, $signatureEncoded)) {
            throw new Exception('Signature invalide');
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            throw new Exception('Payload invalide');
        }

        if (isset($payload['exp']) && time() > $payload['exp']) {
            throw new Exception('Token expiré');
        }

        return $payload;
    }
}
