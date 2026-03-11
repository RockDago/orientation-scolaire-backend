<?php

class Parcours
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
        $stmt = self::getDb()->query("SELECT * FROM parcours ORDER BY label ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("SELECT * FROM parcours WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            INSERT INTO parcours (label, mention, duree, niveau, conditions, description, objectifs, debouches)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($data['label']),
            trim($data['mention'] ?? ''),
            trim($data['duree'] ?? ''),
            trim($data['niveau'] ?? ''),
            trim($data['conditions'] ?? ''),
            trim($data['description'] ?? ''),
            trim($data['objectifs'] ?? ''),
            trim($data['debouches'] ?? ''),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            UPDATE parcours SET label=?, mention=?, duree=?, niveau=?, conditions=?, description=?, objectifs=?, debouches=?
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['label']),
            trim($data['mention'] ?? ''),
            trim($data['duree'] ?? ''),
            trim($data['niveau'] ?? ''),
            trim($data['conditions'] ?? ''),
            trim($data['description'] ?? ''),
            trim($data['objectifs'] ?? ''),
            trim($data['debouches'] ?? ''),
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("DELETE FROM parcours WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function search(string $term): array
    {
        $pdo = self::getDb();
        $like = "%{$term}%";
        $stmt = $pdo->prepare("
            SELECT * FROM parcours
            WHERE label LIKE ? OR mention LIKE ? OR niveau LIKE ?
            ORDER BY label ASC
        ");
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
