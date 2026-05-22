<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/ApiHelpers.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middlewares/AuthMiddleware.php';

class ProfileController
{
    private static function getRequestData(): array
    {
        return ApiHelpers::requestData();
    }

    private static function authUser(?array $currentUser = null): array
    {
        return $currentUser ?: AuthMiddleware::handle();
    }

    public static function show(?array $currentUser = null): void
    {
        $user = self::authUser($currentUser);
        Response::json([
            'message' => 'Profil recupere avec succes',
            'utilisateur' => $user,
            'data' => $user,
        ]);
    }

    public static function me(?array $currentUser = null): void
    {
        self::show($currentUser);
    }

    public static function update(?array $currentUser = null): void
    {
        $authUser = self::authUser($currentUser);
        $data = self::getRequestData();
        $errors = [];

        foreach (['nom', 'prenom', 'nom_utilisateur', 'email'] as $field) {
            if (empty(trim((string) ($data[$field] ?? '')))) {
                $errors[$field] = "Le champ $field est obligatoire";
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse email est invalide";
        }

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $existingEmail = User::findByEmail(trim((string) $data['email']));
        if ($existingEmail && (int) $existingEmail['id'] !== (int) $authUser['id']) {
            Response::json(['message' => 'Email deja utilise'], 409);
        }

        $existingUsername = User::findByNomUtilisateur(trim((string) $data['nom_utilisateur']));
        if ($existingUsername && (int) $existingUsername['id'] !== (int) $authUser['id']) {
            Response::json(['message' => "Nom d'utilisateur deja utilise"], 409);
        }

        User::updateProfile((int) $authUser['id'], $data);
        $updatedUser = User::findById((int) $authUser['id']);

        Response::json([
            'message' => 'Profil mis a jour avec succes',
            'utilisateur' => $updatedUser,
            'data' => $updatedUser,
        ]);
    }

    public static function changePassword(?array $currentUser = null): void
    {
        $authUser = self::authUser($currentUser);
        $data = self::getRequestData();
        $currentPassword = (string) ($data['mot_de_passe_actuel'] ?? '');
        $newPassword = (string) ($data['nouveau_mot_de_passe'] ?? '');
        $errors = [];

        if ($currentPassword === '') {
            $errors['mot_de_passe_actuel'] = 'Le mot de passe actuel est obligatoire';
        }

        if ($newPassword === '') {
            $errors['nouveau_mot_de_passe'] = 'Le nouveau mot de passe est obligatoire';
        } elseif (strlen($newPassword) < 12) {
            $errors['nouveau_mot_de_passe'] = 'Le mot de passe doit contenir au moins 12 caracteres';
        }

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $userWithPassword = User::findWithPasswordById((int) $authUser['id']);
        if (!$userWithPassword) {
            Response::json(['message' => 'Utilisateur introuvable'], 404);
        }

        if (!password_verify($currentPassword, $userWithPassword['mot_de_passe'])) {
            Response::json([
                'message' => 'Mot de passe actuel incorrect',
                'errors' => ['mot_de_passe_actuel' => 'Mot de passe actuel incorrect'],
            ], 422);
        }

        User::resetPassword((int) $authUser['id'], $newPassword);
        $updatedUser = User::findById((int) $authUser['id']);

        Response::json([
            'message' => 'Mot de passe mis a jour avec succes',
            'utilisateur' => $updatedUser,
            'data' => $updatedUser,
        ]);
    }
}
