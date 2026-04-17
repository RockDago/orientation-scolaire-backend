<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Jwt.php';

class AuthController
{
    // ─── Constantes de validation ───────────────────────────────────────────────
    private const MAX_FIELD_LENGTH   = 255;
    private const MIN_PASSWORD_LEN   = 8;
    private const MAX_PASSWORD_LEN   = 72;   // Limite bcrypt
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES    = 15;

    // ─── Parser le body JSON ou form-data (sans exposer les données brutes) ────
    private static function getRequestData(): array
    {
        $data = $_POST ?: [];

        $rawBody  = file_get_contents('php://input');
        $jsonData = json_decode($rawBody, true);

        if (is_array($jsonData)) {
            $data = array_merge($data, $jsonData);
        }

        return $data;
    }

    // ─── Tronquer proprement les strings pour éviter les dépassements mémoire ──
    private static function sanitizeString(?string $value, int $max = self::MAX_FIELD_LENGTH): string
    {
        if ($value === null) return '';
        // Supprime les caractères nuls et limite la longueur
        $cleaned = str_replace("\0", '', $value);
        return mb_substr(trim($cleaned), 0, $max);
    }

    // ─── Valider la force du mot de passe ───────────────────────────────────────
    private static function validatePassword(string $password): ?string
    {
        $len = strlen($password);
        if ($len < self::MIN_PASSWORD_LEN) {
            return 'Le mot de passe doit contenir au moins ' . self::MIN_PASSWORD_LEN . ' caractères';
        }
        if ($len > self::MAX_PASSWORD_LEN) {
            return 'Le mot de passe ne peut pas dépasser ' . self::MAX_PASSWORD_LEN . ' caractères';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return 'Le mot de passe doit contenir au moins une lettre majuscule';
        }
        if (!preg_match('/[0-9]/', $password)) {
            return 'Le mot de passe doit contenir au moins un chiffre';
        }
        return null; // null = valide
    }

    // ─── Rate limiting : vérifier et enregistrer les tentatives de login ────────
    private static function checkRateLimit(string $ip): void
    {
        try {
            $pdo = User::getDb();

            // Nettoyer les anciennes tentatives expirées
            $stmt = $pdo->prepare(
                "DELETE FROM login_attempts 
                 WHERE ip_address = :ip 
                 AND tentative_le < NOW() - INTERVAL :minutes MINUTE"
            );
            $stmt->execute(['ip' => $ip, 'minutes' => self::LOCKOUT_MINUTES]);

            // Compter les tentatives récentes
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM login_attempts 
                 WHERE ip_address = :ip 
                 AND tentative_le > NOW() - INTERVAL :minutes MINUTE"
            );
            $stmt->execute(['ip' => $ip, 'minutes' => self::LOCKOUT_MINUTES]);
            $count = (int) $stmt->fetchColumn();

            if ($count >= self::MAX_LOGIN_ATTEMPTS) {
                Response::json([
                    'message' => 'Trop de tentatives de connexion. Réessayez dans ' . self::LOCKOUT_MINUTES . ' minutes.'
                ], 429);
                exit;
            }
        } catch (Exception $e) {
            // Si la table n'existe pas encore, on ne bloque pas — on logue seulement
            error_log('[AuthController::checkRateLimit] ' . $e->getMessage());
        }
    }

    private static function recordFailedAttempt(string $ip): void
    {
        try {
            $pdo  = User::getDb();
            $stmt = $pdo->prepare(
                "INSERT INTO login_attempts (ip_address, tentative_le) VALUES (:ip, NOW())"
            );
            $stmt->execute(['ip' => $ip]);
        } catch (Exception $e) {
            error_log('[AuthController::recordFailedAttempt] ' . $e->getMessage());
        }
    }

    private static function clearLoginAttempts(string $ip): void
    {
        try {
            $pdo  = User::getDb();
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip");
            $stmt->execute(['ip' => $ip]);
        } catch (Exception $e) {
            error_log('[AuthController::clearLoginAttempts] ' . $e->getMessage());
        }
    }

    // ─── Obtenir l'IP réelle du client ──────────────────────────────────────────
    private static function getClientIp(): string
    {
        // Gère les proxies tout en évitant la falsification
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded) {
            // Prendre la première IP (la plus proche du client)
            $ips = array_map('trim', explode(',', $forwarded));
            $ip  = filter_var($ips[0], FILTER_VALIDATE_IP);
            if ($ip) return $ip;
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // REGISTER — protégé par le middleware (admin requis) dans index.php
    // ═══════════════════════════════════════════════════════════════════════════
    public static function register(): void
    {
        $data = self::getRequestData();

        $requiredFields = ['nom', 'prenom', 'nom_utilisateur', 'email', 'mot_de_passe'];
        $errors = [];

        // Validation des champs requis + longueur max
        foreach ($requiredFields as $field) {
            $raw = $data[$field] ?? '';
            if (empty(trim($raw))) {
                $errors[$field] = "Le champ $field est obligatoire";
            } elseif (strlen($raw) > self::MAX_FIELD_LENGTH) {
                $errors[$field] = "Le champ $field ne peut pas dépasser " . self::MAX_FIELD_LENGTH . " caractères";
            }
        }

        // Validation email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'adresse email est invalide";
        }

        // Validation mot de passe
        if (empty($errors['mot_de_passe']) && isset($data['mot_de_passe'])) {
            $pwdError = self::validatePassword($data['mot_de_passe']);
            if ($pwdError !== null) {
                $errors['mot_de_passe'] = $pwdError;
            }
        }

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
            exit;
        }

        // Vérifier l'unicité email et username
        if (User::findByEmail(self::sanitizeString($data['email']))) {
            Response::json(['message' => 'Email déjà utilisé'], 409);
            exit;
        }

        if (User::findByNomUtilisateur(self::sanitizeString($data['nom_utilisateur']))) {
            Response::json(['message' => "Nom d'utilisateur déjà utilisé"], 409);
            exit;
        }

        // Validation username (alphanumérique + tirets)
        $username = self::sanitizeString($data['nom_utilisateur']);
        if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,50}$/', $username)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors'  => ['nom_utilisateur' => "Le nom d'utilisateur ne peut contenir que des lettres, chiffres, tirets et underscores (3-50 caractères)"]
            ], 422);
            exit;
        }

        $id = User::create([
            'role'            => 'admin',
            'nom'             => self::sanitizeString($data['nom']),
            'prenom'          => self::sanitizeString($data['prenom']),
            'nom_utilisateur' => $username,
            'email'           => self::sanitizeString($data['email']),
            'telephone'       => self::sanitizeString($data['telephone'] ?? '', 20),
            'adresse'         => self::sanitizeString($data['adresse'] ?? '', 500),
            'code_postal'     => self::sanitizeString($data['code_postal'] ?? '', 10),
            'mot_de_passe'    => $data['mot_de_passe'],
        ]);

        $user = User::findById($id);

        Response::json([
            'message'     => 'Utilisateur créé avec succès',
            'utilisateur' => $user,
        ], 201);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // LOGIN — avec rate limiting, sans exposition de données sensibles
    // ═══════════════════════════════════════════════════════════════════════════
    public static function login(): void
    {
        $ip   = self::getClientIp();
        $data = self::getRequestData();

        // ── CRIT-6 : Vérifier le rate limiting avant tout traitement ──────────
        self::checkRateLimit($ip);

        // ── Extraire l'identifiant (email ou nom_utilisateur) ─────────────────
        $identifiant = '';
        if (!empty($data['identifiant'])) {
            $identifiant = self::sanitizeString($data['identifiant']);
        } elseif (!empty($data['nom_utilisateur'])) {
            $identifiant = self::sanitizeString($data['nom_utilisateur']);
        } elseif (!empty($data['email'])) {
            $identifiant = self::sanitizeString($data['email']);
        }

        $motDePasse = isset($data['mot_de_passe']) ? trim($data['mot_de_passe']) : '';

        // ── CRIT-1 : Réponse générique sans exposer de données internes ───────
        if ($identifiant === '' || $motDePasse === '') {
            Response::json(['message' => 'Identifiant et mot de passe obligatoires'], 422);
            exit;
        }

        // ── Longueur max pour éviter les attaques DoS ─────────────────────────
        if (strlen($identifiant) > self::MAX_FIELD_LENGTH || strlen($motDePasse) > self::MAX_PASSWORD_LEN) {
            self::recordFailedAttempt($ip);
            // Message générique pour ne pas indiquer quel champ est invalide
            Response::json(['message' => 'Identifiant ou mot de passe invalide'], 401);
            exit;
        }

        $user = User::findByIdentifiant($identifiant);

        // ── Message identique que l'user existe ou non (évite l'énumération) ──
        if (!$user || !password_verify($motDePasse, $user['mot_de_passe'])) {
            self::recordFailedAttempt($ip);
            Response::json(['message' => 'Identifiant ou mot de passe invalide'], 401);
            exit;
        }

        // ── Connexion réussie : effacer les tentatives ────────────────────────
        self::clearLoginAttempts($ip);

        $config = require __DIR__ . '/../config/database.php';

        $payload = [
            'sub'   => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'],
            'exp'   => time() + 86400,
            'iat'   => time(),
        ];

        $token = Jwt::encode($payload, $config['jwt_secret']);

        Response::json([
            'message'     => 'Connexion réussie',
            'token'       => $token,
            'utilisateur' => [
                'id'             => $user['id'],
                'role'           => $user['role'],
                'nom'            => $user['nom'],
                'prenom'         => $user['prenom'],
                'nom_utilisateur'=> $user['nom_utilisateur'],
                'email'          => $user['email'],
                'telephone'      => $user['telephone'],
                'adresse'        => $user['adresse'],
                'code_postal'    => $user['code_postal'],
            ]
        ]);
    }
}
