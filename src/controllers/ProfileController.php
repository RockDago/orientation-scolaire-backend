<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';


class ProfileController {
    private static function getRequestData(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }

        parse_str(file_get_contents('php://input'), $putData);
        return $putData ?: [];
    }

    public static function show(): void {
        $user = AuthMiddleware::handle();
        Response::json([
            'message' => 'Profil récupéré avec succès',
            'utilisateur' => $user
        ]);
    }

    
    public static function me(): void {
        self::show();
    }

    public static function update(): void {
        $authUser = AuthMiddleware::handle();
        $data = self::getRequestData();

        $requiredFields = ['nom', 'prenom', 'nom_utilisateur', 'email'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                Response::json(['message' => "Le champ $field est obligatoire"], 422);
            }
        }

        $existingEmail = User::findByEmail($data['email']);
        if ($existingEmail && (int)$existingEmail['id'] !== (int)$authUser['id']) {
            Response::json(['message' => 'Email déjà utilisé'], 409);
        }

        $existingUsername = User::findByNomUtilisateur($data['nom_utilisateur']);
        if ($existingUsername && (int)$existingUsername['id'] !== (int)$authUser['id']) {
            Response::json(['message' => 'Nom d’utilisateur déjà utilisé'], 409);
        }

        User::updateProfile((int)$authUser['id'], $data);
        $updatedUser = User::findById((int)$authUser['id']);

        Response::json([
            'message' => 'Profil mis à jour avec succès',
            'utilisateur' => $updatedUser
        ]);
    }
}
