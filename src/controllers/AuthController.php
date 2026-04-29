<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Jwt.php';

class AuthController
{
    private static function getRequestData(): array
    {
        $data = $_POST ?: [];
        $rawBody = file_get_contents('php://input');
        $jsonData = json_decode($rawBody, true);

        if (is_array($jsonData)) {
            $data = array_merge($data, $jsonData);
        }

        return $data;
    }

    public static function register(?array $currentUser = null): void
    {
        if (($currentUser['role'] ?? null) !== 'admin') {
            Response::json(['message' => 'Acces refuse'], 403);
        }

        $data = self::getRequestData();
        $requiredFields = ['nom', 'prenom', 'nom_utilisateur', 'email', 'mot_de_passe'];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (empty(trim((string) ($data[$field] ?? '')))) {
                $errors[$field] = "Le champ $field est obligatoire";
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse email est invalide";
        }

        if (!empty($data['mot_de_passe']) && strlen((string) $data['mot_de_passe']) < 12) {
            $errors['mot_de_passe'] = 'Le mot de passe doit contenir au moins 12 caracteres';
        }

        if (!empty($errors)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors' => $errors,
            ], 422);
        }

        if (User::findByEmail(trim((string) $data['email']))) {
            Response::json(['message' => 'Email deja utilise'], 409);
        }

        if (User::findByNomUtilisateur(trim((string) $data['nom_utilisateur']))) {
            Response::json(['message' => 'Nom utilisateur deja utilise'], 409);
        }

        $id = User::create([
            'role' => 'admin',
            'nom' => trim((string) $data['nom']),
            'prenom' => trim((string) $data['prenom']),
            'nom_utilisateur' => trim((string) $data['nom_utilisateur']),
            'email' => trim((string) $data['email']),
            'telephone' => trim((string) ($data['telephone'] ?? '')),
            'adresse' => trim((string) ($data['adresse'] ?? '')),
            'code_postal' => trim((string) ($data['code_postal'] ?? '')),
            'mot_de_passe' => (string) $data['mot_de_passe'],
        ]);

        $user = User::findById($id);

        Response::json([
            'message' => 'Utilisateur cree avec succes',
            'utilisateur' => $user,
        ], 201);
    }

    public static function login(): void
    {
        $data = self::getRequestData();

        $identifiant = '';
        if (isset($data['identifiant']) && trim((string) $data['identifiant']) !== '') {
            $identifiant = trim((string) $data['identifiant']);
        } elseif (isset($data['nom_utilisateur']) && trim((string) $data['nom_utilisateur']) !== '') {
            $identifiant = trim((string) $data['nom_utilisateur']);
        } elseif (isset($data['email']) && trim((string) $data['email']) !== '') {
            $identifiant = trim((string) $data['email']);
        }

        $motDePasse = isset($data['mot_de_passe']) ? trim((string) $data['mot_de_passe']) : '';

        if ($identifiant === '' || $motDePasse === '') {
            Response::json([
                'message' => 'Identifiant et mot de passe obligatoires',
            ], 422);
        }

        $user = User::findByIdentifiant($identifiant);
        if (!$user) {
            Response::json(['message' => 'Identifiants invalides'], 401);
        }

        if (!password_verify($motDePasse, $user['mot_de_passe'])) {
            Response::json(['message' => 'Identifiants invalides'], 401);
        }

        if (isset($user['est_actif']) && (int) $user['est_actif'] === 0) {
            Response::json(['message' => 'Votre compte est desactive. Veuillez contacter l administrateur.'], 403);
        }

        $config = require __DIR__ . '/../config/database.php';
        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'exp' => time() + 86400,
        ];

        $token = Jwt::encode($payload, $config['jwt_secret']);

        Response::json([
            'message' => 'Connexion reussie',
            'token' => $token,
            'utilisateur' => [
                'id' => $user['id'],
                'role' => $user['role'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'nom_utilisateur' => $user['nom_utilisateur'],
                'email' => $user['email'],
                'telephone' => $user['telephone'],
                'adresse' => $user['adresse'],
                'code_postal' => $user['code_postal'],
            ],
        ]);
    }
}
