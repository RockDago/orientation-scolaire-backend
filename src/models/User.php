<?php

require_once __DIR__ . '/../core/Database.php';

class User {

    private const MAX_FIELD = 255;

    // ── Exposer la connexion PDO pour le rate limiting dans AuthController ────
    public static function getDb(): PDO
    {
        return Database::getConnection();
    }

    public static function findByEmail(string $email): ?array {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByNomUtilisateur(string $nomUtilisateur): ?array {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = :nom_utilisateur LIMIT 1");
        $stmt->execute(['nom_utilisateur' => $nomUtilisateur]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByIdentifiant(string $identifiant): ?array {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT * FROM utilisateurs 
             WHERE email = :identifiant OR nom_utilisateur = :identifiant 
             LIMIT 1"
        );
        $stmt->execute(['identifiant' => $identifiant]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array {
        $pdo  = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, role, nom, prenom, nom_utilisateur, email, telephone, adresse, code_postal, cree_le, modifie_le
            FROM utilisateurs
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();

        // CRIT-9 : Valider la longueur du mot de passe AVANT bcrypt (max 72 bytes)
        $mdp = $data['mot_de_passe'] ?? '';
        if (strlen($mdp) < 8 || strlen($mdp) > 72) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir entre 8 et 72 caractères');
        }

        $sql = "INSERT INTO utilisateurs (
                    role, nom, prenom, nom_utilisateur, email, telephone, adresse, code_postal, mot_de_passe
                ) VALUES (
                    :role, :nom, :prenom, :nom_utilisateur, :email, :telephone, :adresse, :code_postal, :mot_de_passe
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'role'            => $data['role'] ?? 'admin',
            'nom'             => mb_substr($data['nom'],  0, self::MAX_FIELD),
            'prenom'          => mb_substr($data['prenom'], 0, self::MAX_FIELD),
            'nom_utilisateur' => mb_substr($data['nom_utilisateur'], 0, 50),
            'email'           => mb_substr($data['email'], 0, self::MAX_FIELD),
            'telephone'       => isset($data['telephone'])   ? mb_substr($data['telephone'], 0, 20)    : null,
            'adresse'         => isset($data['adresse'])     ? mb_substr($data['adresse'], 0, 500)     : null,
            'code_postal'     => isset($data['code_postal']) ? mb_substr($data['code_postal'], 0, 10)  : null,
            'mot_de_passe'    => password_hash($mdp, PASSWORD_BCRYPT),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateProfile(int $id, array $data): bool {
        $pdo = Database::getConnection();

        $sql = "UPDATE utilisateurs SET
                    nom              = :nom,
                    prenom           = :prenom,
                    nom_utilisateur  = :nom_utilisateur,
                    email            = :email,
                    telephone        = :telephone,
                    adresse          = :adresse,
                    code_postal      = :code_postal
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            'id'              => $id,
            'nom'             => mb_substr($data['nom'] ?? '', 0, self::MAX_FIELD),
            'prenom'          => mb_substr($data['prenom'] ?? '', 0, self::MAX_FIELD),
            'nom_utilisateur' => mb_substr($data['nom_utilisateur'] ?? '', 0, 50),
            'email'           => mb_substr($data['email'] ?? '', 0, self::MAX_FIELD),
            'telephone'       => isset($data['telephone'])   ? mb_substr($data['telephone'], 0, 20)   : null,
            'adresse'         => isset($data['adresse'])     ? mb_substr($data['adresse'], 0, 500)    : null,
            'code_postal'     => isset($data['code_postal']) ? mb_substr($data['code_postal'], 0, 10) : null,
        ]);
    }
}
