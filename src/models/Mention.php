<?php

class Mention
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
        $stmt = self::getDb()->query("
            SELECT m.*, d.label as domaine_label 
            FROM mentions m 
            LEFT JOIN domaines d ON m.domaine_id = d.id 
            ORDER BY m.label ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            SELECT m.*, d.label as domaine_label 
            FROM mentions m 
            LEFT JOIN domaines d ON m.domaine_id = d.id 
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByLabel(string $label): ?array
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("SELECT * FROM mentions WHERE label = ?");
        $stmt->execute([$label]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("INSERT INTO mentions (label, description, domaine_id) VALUES (?, ?, ?)");
        $stmt->execute([
            trim($data['label']), 
            trim($data['description'] ?? ''),
            $data['domaine_id'] ?? null
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("UPDATE mentions SET label = ?, description = ?, domaine_id = ? WHERE id = ?");
        return $stmt->execute([
            trim($data['label']), 
            trim($data['description'] ?? ''), 
            $data['domaine_id'] ?? null,
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("DELETE FROM mentions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function search(string $term): array
    {
        $pdo = self::getDb();
        $like = "%{$term}%";
        $stmt = $pdo->prepare("
            SELECT m.*, d.label as domaine_label 
            FROM mentions m 
            LEFT JOIN domaines d ON m.domaine_id = d.id 
            WHERE m.label LIKE ? OR m.description LIKE ? OR d.label LIKE ? 
            ORDER BY m.label ASC
        ");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
