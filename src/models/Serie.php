<?php

require_once __DIR__ . '/../config/database.php';

class Serie
{
    private static function getDb(): PDO
    {
        $config = require __DIR__ . '/../config/database.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $pdo;
    }

    public static function findAll(): array
    {
        $pdo = self::getDb();
        $stmt = $pdo->query("SELECT * FROM series ORDER BY code ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("SELECT * FROM series WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("SELECT * FROM series WHERE code = ?");
        $stmt->execute([$code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            INSERT INTO series (code, label, description)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            trim($data['code']),
            trim($data['label']),
            trim($data['description'] ?? ''),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            UPDATE series SET code = ?, label = ?, description = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['code']),
            trim($data['label']),
            trim($data['description'] ?? ''),
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("DELETE FROM series WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function search(string $term): array
    {
        $pdo = self::getDb();
        $like = "%{$term}%";
        $stmt = $pdo->prepare("
            SELECT * FROM series
            WHERE label LIKE ? OR code LIKE ?
            ORDER BY code ASC
        ");
        $stmt->execute([$like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
