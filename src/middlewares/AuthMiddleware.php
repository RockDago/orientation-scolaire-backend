<?php

require_once __DIR__ . '/../core/Jwt.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    public static function handle(): array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            Response::json(['message' => 'Token manquant'], 401);
        }

        $token = trim(substr($authHeader, 7));
        $config = require __DIR__ . '/../config/database.php';

        try {
            $payload = Jwt::decode($token, $config['jwt_secret']);
            $user = User::findById((int)$payload['sub']);

            if (!$user) {
                Response::json(['message' => 'Utilisateur introuvable'], 401);
            }

            return $user;
        } catch (Exception $e) {
            Response::json([
                'message' => 'Non autorisé',
                'error' => $e->getMessage()
            ], 401);
        }
    }
}
