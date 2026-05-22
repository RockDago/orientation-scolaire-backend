<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/ApiHelpers.php';

class UserController
{
    private static function requestData(): array
    {
        return ApiHelpers::requestData();
    }

    private static function validateUpdate(array $data): array
    {
        $errors = [];

        foreach (['nom', 'prenom', 'nom_utilisateur', 'email'] as $field) {
            if (empty(trim((string) ($data[$field] ?? '')))) {
                $errors[$field] = "Le champ $field est obligatoire";
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse email est invalide";
        }

        return $errors;
    }

    public static function index($currentUser): void
    {
        $users = ApiHelpers::filterItems(User::all(), [
            'role' => 'role',
            'status' => 'est_actif',
        ]);

        Response::json(ApiHelpers::listResponse('users', $users));
    }

    public static function update($id, $currentUser): void
    {
        $id = (int) $id;
        $existingUser = User::findById($id);
        if (!$existingUser) {
            Response::json(['message' => 'Utilisateur introuvable'], 404);
        }

        $data = self::requestData();
        $errors = self::validateUpdate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $existingEmail = User::findByEmail(trim((string) $data['email']));
        if ($existingEmail && (int) $existingEmail['id'] !== $id) {
            Response::json(['message' => 'Email deja utilise'], 409);
        }

        $existingUsername = User::findByNomUtilisateur(trim((string) $data['nom_utilisateur']));
        if ($existingUsername && (int) $existingUsername['id'] !== $id) {
            Response::json(['message' => "Nom d'utilisateur deja utilise"], 409);
        }

        if (!User::update($id, $data)) {
            Response::json(['message' => 'Erreur lors de la mise a jour'], 500);
        }

        $updatedUser = User::findById($id);
        Response::json([
            'message' => 'Utilisateur mis a jour avec succes',
            'utilisateur' => $updatedUser,
            'data' => $updatedUser,
        ]);
    }

    public static function toggleStatus($id, $currentUser): void
    {
        $id = (int) $id;
        $user = User::findById($id);
        if (!$user) {
            Response::json(['message' => 'Utilisateur introuvable'], 404);
        }

        $data = self::requestData();
        $status = isset($data['est_actif']) ? (int) $data['est_actif'] : 1;
        $status = $status === 1 ? 1 : 0;

        if ((int) $currentUser['id'] === $id && $status === 0) {
            Response::json(['message' => 'Vous ne pouvez pas desactiver votre propre compte'], 400);
        }

        if (!User::toggleStatus($id, $status)) {
            Response::json(['message' => 'Erreur lors de la mise a jour du statut'], 500);
        }

        $updatedUser = User::findById($id);
        Response::json([
            'message' => 'Statut mis a jour avec succes',
            'utilisateur' => $updatedUser,
            'status' => $status,
            'data' => $updatedUser,
        ]);
    }

    public static function resetPassword($id, $currentUser): void
    {
        $id = (int) $id;
        if (!User::findById($id)) {
            Response::json(['message' => 'Utilisateur introuvable'], 404);
        }

        $data = self::requestData();
        $password = (string) ($data['mot_de_passe'] ?? '');

        if ($password === '') {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors' => ['mot_de_passe' => 'Le mot de passe est obligatoire'],
            ], 422);
        }

        if (strlen($password) < 12) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors' => ['mot_de_passe' => 'Le mot de passe doit contenir au moins 12 caracteres'],
            ], 422);
        }

        if (!User::resetPassword($id, $password)) {
            Response::json(['message' => 'Erreur lors de la reinitialisation'], 500);
        }

        $updatedUser = User::findById($id);
        Response::json([
            'message' => 'Mot de passe reinitialise avec succes',
            'utilisateur' => $updatedUser,
            'data' => $updatedUser,
        ]);
    }

    public static function destroy($id, $currentUser): void
    {
        $id = (int) $id;
        if ($id === (int) $currentUser['id']) {
            Response::json(['message' => 'Vous ne pouvez pas supprimer votre propre compte'], 400);
        }

        $user = User::findById($id);
        if (!$user) {
            Response::json(['message' => 'Utilisateur introuvable'], 404);
        }

        if (!User::delete($id)) {
            Response::json(['message' => 'Erreur lors de la suppression'], 500);
        }

        Response::json([
            'message' => 'Utilisateur supprime avec succes',
            'utilisateur' => $user,
            'data' => $user,
        ]);
    }
}
