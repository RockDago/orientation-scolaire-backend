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

    public static function register(): void
    {
        $data = self::getRequestData();

        $requiredFields = ['nom', 'prenom', 'nom_utilisateur', 'email', 'mot_de_passe'];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (empty(trim($data[$field] ?? ''))) {
                $errors[$field] = "Le champ $field est obligatoire";
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse email est invalide";
        }

        if (!empty($errors)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ], 422);
        }

        if (User::findByEmail($data['email'])) {
            Response::json(['message' => 'Email déjà utilisé'], 409);
        }

        if (User::findByNomUtilisateur($data['nom_utilisateur'])) {
            Response::json(['message' => 'Nom d’utilisateur déjà utilisé'], 409);
        }

        $id = User::create([
            'role' => 'admin',
            'nom' => trim($data['nom']),
            'prenom' => trim($data['prenom']),
            'nom_utilisateur' => trim($data['nom_utilisateur']),
            'email' => trim($data['email']),
            'telephone' => trim($data['telephone'] ?? ''),
            'adresse' => trim($data['adresse'] ?? ''),
            'code_postal' => trim($data['code_postal'] ?? ''),
            'mot_de_passe' => $data['mot_de_passe'],
        ]);

        $user = User::findById($id);

        Response::json([
            'message' => 'Utilisateur créé avec succès',
            'utilisateur' => $user,
        ], 201);
    }

    public static function login(): void
    {
        $data = self::getRequestData();

        $identifiant = '';
        if (isset($data['identifiant']) && trim($data['identifiant']) !== '') {
            $identifiant = trim($data['identifiant']);
        } elseif (isset($data['nom_utilisateur']) && trim($data['nom_utilisateur']) !== '') {
            $identifiant = trim($data['nom_utilisateur']);
        } elseif (isset($data['email']) && trim($data['email']) !== '') {
            $identifiant = trim($data['email']);
        }

        $motDePasse = isset($data['mot_de_passe']) ? trim($data['mot_de_passe']) : '';

        if ($identifiant === '' || $motDePasse === '') {
            Response::json([
                'message' => 'Identifiant et mot de passe obligatoires',
                'debug_info' => [
                    'explication' => 'L\'identifiant ou le mot de passe est vide après nettoyage',
                    'data_final' => $data,
                    'identifiant_trouve' => $identifiant,
                    'mdp_trouve' => $motDePasse
                ]
            ], 422);
        }

        $user = User::findByIdentifiant($identifiant);

        if (!$user) {
            Response::json([
                'message' => 'Aucun utilisateur trouvé avec cet email ou nom d’utilisateur'
            ], 401);
        }

        if (!password_verify($motDePasse, $user['mot_de_passe'])) {
            Response::json([
                'message' => 'Mot de passe incorrect'
            ], 401);
        }

        $config = require __DIR__ . '/../config/database.php';

        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'exp' => time() + 86400
        ];

        $token = Jwt::encode($payload, $config['jwt_secret']);

        Response::json([
            'message' => 'Connexion réussie',
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
            ]
        ]);
    }
}
