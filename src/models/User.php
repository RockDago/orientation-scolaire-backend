<?php

require_once __DIR__ . '/../core/Database.php';

class User {
    public static function findByEmail(string $email): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByNomUtilisateur(string $nomUtilisateur): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = :nom_utilisateur LIMIT 1");
        $stmt->execute(['nom_utilisateur' => $nomUtilisateur]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findByIdentifiant(string $identifiant): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :identifiant OR nom_utilisateur = :identifiant LIMIT 1");
        $stmt->execute(['identifiant' => $identifiant]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array {
        $pdo = Database::getConnection();
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

        $sql = "INSERT INTO utilisateurs (
                    role, nom, prenom, nom_utilisateur, email, telephone, adresse, code_postal, mot_de_passe
                ) VALUES (
                    :role, :nom, :prenom, :nom_utilisateur, :email, :telephone, :adresse, :code_postal, :mot_de_passe
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'role' => $data['role'] ?? 'admin',
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'nom_utilisateur' => $data['nom_utilisateur'],
            'email' => $data['email'],
            'telephone' => $data['telephone'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'code_postal' => $data['code_postal'] ?? null,
            'mot_de_passe' => password_hash($data['mot_de_passe'], PASSWORD_BCRYPT),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateProfile(int $id, array $data): bool {
        $pdo = Database::getConnection();

        $sql = "UPDATE utilisateurs SET
                    nom = :nom,
                    prenom = :prenom,
                    nom_utilisateur = :nom_utilisateur,
                    email = :email,
                    telephone = :telephone,
                    adresse = :adresse,
                    code_postal = :code_postal
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'nom_utilisateur' => $data['nom_utilisateur'],
            'email' => $data['email'],
            'telephone' => $data['telephone'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'code_postal' => $data['code_postal'] ?? null,
        ]);
    }
}
