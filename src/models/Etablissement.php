<?php

class Etablissement
{
    private static function getDb(): PDO
    {
        $config = require __DIR__ . '/../config/database.php';
        return new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public static function findAll(): array
    {
        $stmt = self::getDb()->query("SELECT * FROM etablissements ORDER BY nom ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("SELECT * FROM etablissements WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            INSERT INTO etablissements (nom, province, region, type, mention, parcours, metier, niveau, duree, admission, contact)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($data['nom']),
            trim($data['province'] ?? ''),
            trim($data['region'] ?? ''),
            trim($data['type'] ?? 'Public'),
            trim($data['mention'] ?? ''),
            trim($data['parcours'] ?? ''),
            trim($data['metier'] ?? ''),
            trim($data['niveau'] ?? ''),
            trim($data['duree'] ?? ''),
            trim($data['admission'] ?? ''),
            trim($data['contact'] ?? ''),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            UPDATE etablissements
            SET nom=?, province=?, region=?, type=?, mention=?, parcours=?, metier=?, niveau=?, duree=?, admission=?, contact=?
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['nom']),
            trim($data['province'] ?? ''),
            trim($data['region'] ?? ''),
            trim($data['type'] ?? 'Public'),
            trim($data['mention'] ?? ''),
            trim($data['parcours'] ?? ''),
            trim($data['metier'] ?? ''),
            trim($data['niveau'] ?? ''),
            trim($data['duree'] ?? ''),
            trim($data['admission'] ?? ''),
            trim($data['contact'] ?? ''),
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("DELETE FROM etablissements WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function search(string $term): array
    {
        $pdo = self::getDb();
        $like = "%{$term}%";
        $stmt = $pdo->prepare("
            SELECT * FROM etablissements
            WHERE nom LIKE ? OR province LIKE ? OR region LIKE ? OR mention LIKE ? OR parcours LIKE ? OR metier LIKE ?
            ORDER BY nom ASC
        ");
        $stmt->execute([$like, $like, $like, $like, $like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
