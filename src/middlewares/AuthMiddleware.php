<?php

require_once __DIR__ . '/../core/Jwt.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/User.php';

class AuthMiddleware {
    /**
     * Valide le JWT et retourne les infos utilisateur
     * 
     * @return array Infos utilisateur décodées
     */
    public static function handle(): array {
        // Headers case-insensitive
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $authHeader = $headers['authorization'] ?? '';

        // Vérif Bearer token
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            Response::json(['message' => 'Token Bearer manquant'], 401);
            exit;
        }

        // Extraction token
        $token = trim(substr($authHeader, 7));
        if (empty($token)) {
            Response::json(['message' => 'Token vide'], 401);
            exit;
        }

        // Config JWT
        $config = require __DIR__ . '/../config/database.php';

        try {
            // Décodage JWT
            $payload = Jwt::decode($token, $config['jwt_secret']);
            
            // Vérif utilisateur existe en DB
            $user = User::findById((int)$payload['sub']);
            if (!$user) {
                Response::json(['message' => 'Utilisateur introuvable'], 401);
                exit;
            }

            // Token valide + user OK
            return $user;
            
        } catch (Exception $e) {
            // Erreur précise pour debug (à supprimer en prod)
            Response::json([
                'message' => 'Token invalide',
                'error' => $e->getMessage()
            ], 401);
            exit;
        }
    }
}
