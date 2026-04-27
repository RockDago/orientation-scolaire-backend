<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Response.php';

class UserController {
    public static function index($currentUser): void {
        $users = User::all();
        Response::json($users);
    }

    public static function update($id, $currentUser): void {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);

        if (User::update((int)$id, $data)) {
            $updatedUser = User::findById((int)$id);
            Response::json(['message' => 'Utilisateur mis à jour avec succès', 'utilisateur' => $updatedUser]);
        } else {
            Response::json(['message' => 'Erreur lors de la mise à jour'], 500);
        }
    }

    public static function toggleStatus($id, $currentUser): void {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        $status = isset($data['est_actif']) ? (int)$data['est_actif'] : 1;

        if (User::toggleStatus((int)$id, $status)) {
            Response::json(['message' => 'Statut mis à jour avec succès']);
        } else {
            Response::json(['message' => 'Erreur lors de la mise à jour du statut'], 500);
        }
    }

    public static function resetPassword($id, $currentUser): void {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        $password = $data['mot_de_passe'] ?? '';

        if (empty($password)) {
            Response::json(['message' => 'Le mot de passe est obligatoire'], 422);
            return;
        }

        if (User::resetPassword((int)$id, $password)) {
            Response::json(['message' => 'Mot de passe réinitialisé avec succès']);
        } else {
            Response::json(['message' => 'Erreur lors de la réinitialisation'], 500);
        }
    }

    public static function destroy($id, $currentUser): void {
        if ($id === $currentUser['id']) {
            Response::json(['message' => 'Vous ne pouvez pas supprimer votre propre compte'], 400);
        }

        if (User::delete($id)) {
            Response::json(['message' => 'Utilisateur supprimé avec succès']);
        } else {
            Response::json(['message' => 'Erreur lors de la suppression'], 500);
        }
    }
}
