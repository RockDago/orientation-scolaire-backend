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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'formatParcours'], $results);
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("SELECT * FROM parcours WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? self::formatParcours($result) : null;
    }

    private static function formatParcours(array $parcours): array
    {
        $jsonFields = ['niveau', 'duree'];
        foreach ($jsonFields as $field) {
            if (isset($parcours[$field]) && is_string($parcours[$field])) {
                $decoded = json_decode($parcours[$field], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $parcours[$field] = $decoded;
                } else {
                    // Fallback for legacy plain string data
                    $val = trim($parcours[$field]);
                    $parcours[$field] = $val !== "" ? [$val] : [];
                }
            } elseif (!isset($parcours[$field]) || is_null($parcours[$field])) {
                $parcours[$field] = [];
            }
        }
        return $parcours;
    }

    public static function create(array $data): int
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            INSERT INTO parcours (label, mention, duree, niveau)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($data['label']),
            trim($data['mention'] ?? ''),
            json_encode($data['duree'] ?? []),
            json_encode($data['niveau'] ?? []),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            UPDATE parcours SET label=?, mention=?, duree=?, niveau=?
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['label']),
            trim($data['mention'] ?? ''),
            json_encode($data['duree'] ?? []),
            json_encode($data['niveau'] ?? []),
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
            WHERE label LIKE ? OR mention LIKE ?
            ORDER BY label ASC
        ");
        $stmt->execute([$like, $like]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'formatParcours'], $results);
    }
}
