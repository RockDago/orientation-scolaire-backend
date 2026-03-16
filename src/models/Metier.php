<?php

class Metier
{
    // ✅ Singleton PDO — même connexion pour lastInsertId() fiable
    private static ?PDO $pdo = null;

    private static function getDb(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../config/database.php';
            self::$pdo = new PDO(
                "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8",
                $config['username'],
                $config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$pdo;
    }

    public static function findAll(): array
    {
        $stmt = self::getDb()->query("SELECT * FROM metiers ORDER BY label ASC");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'formatMetier'], $results);
    }

    public static function findById(int $id): ?array
    {
        $pdo  = self::getDb();
        $stmt = $pdo->prepare("SELECT * FROM metiers WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? self::formatMetier($result) : null;
    }

    private static function formatMetier(array $metier): array
    {
        return [
            ...$metier,
            'parcours'          => (is_string($metier['parcours'])          ? json_decode($metier['parcours'], true)          : []) ?? [],
            'serie'             => (is_string($metier['serie'])             ? json_decode($metier['serie'], true)             : []) ?? [],
            'parcoursFormation' => (is_string($metier['parcoursFormation']) ? json_decode($metier['parcoursFormation'], true) : []) ?? [],
        ];
    }

    public static function create(array $data): int
    {
        $pdo  = self::getDb();
        $stmt = $pdo->prepare("
            INSERT INTO metiers (label, description, parcours, mention, domaine, serie, niveau, parcoursFormation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($data['label']),
            trim($data['description']  ?? ''),
            json_encode($data['parcours']          ?? [], JSON_UNESCAPED_UNICODE),
            trim($data['mention']      ?? ''),
            trim($data['domaine']      ?? ''),
            json_encode($data['serie']             ?? [], JSON_UNESCAPED_UNICODE),
            trim($data['niveau']       ?? ''),
            json_encode($data['parcoursFormation'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        // ✅ lastInsertId() sur la même connexion → toujours correct
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo  = self::getDb();
        $stmt = $pdo->prepare("
            UPDATE metiers
            SET label=?, description=?, parcours=?, mention=?, domaine=?, serie=?, niveau=?, parcoursFormation=?
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['label']),
            trim($data['description']  ?? ''),
            json_encode($data['parcours']          ?? [], JSON_UNESCAPED_UNICODE),
            trim($data['mention']      ?? ''),
            trim($data['domaine']      ?? ''),
            json_encode($data['serie']             ?? [], JSON_UNESCAPED_UNICODE),
            trim($data['niveau']       ?? ''),
            json_encode($data['parcoursFormation'] ?? [], JSON_UNESCAPED_UNICODE),
            $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo  = self::getDb();
        $stmt = $pdo->prepare("DELETE FROM metiers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function search(string $term): array
    {
        $pdo  = self::getDb();
        $like = "%{$term}%";
        $stmt = $pdo->prepare("
            SELECT * FROM metiers
            WHERE label LIKE ? OR description LIKE ? OR parcours LIKE ? OR mention LIKE ?
            ORDER BY label ASC
        ");
        $stmt->execute([$like, $like, $like, $like]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'formatMetier'], $results);
    }
}
