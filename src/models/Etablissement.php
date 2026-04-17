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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'formatEtablissement'], $results);
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("SELECT * FROM etablissements WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? self::formatEtablissement($result) : null;
    }

    private static function formatEtablissement(array $etab): array
    {
        $jsonFields = ['parcours', 'mention', 'domaine', 'metier', 'niveau', 'admission'];
        foreach ($jsonFields as $field) {
            if (isset($etab[$field]) && is_string($etab[$field])) {
                $etab[$field] = json_decode($etab[$field], true) ?: [];
            } elseif (!isset($etab[$field])) {
                $etab[$field] = [];
            }
        }
        return $etab;
    }

    public static function create(array $data): int
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            INSERT INTO etablissements (nom, province, region, type, mention, domaine, parcours, metier, niveau, admission, contact)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            trim($data['nom']),
            trim($data['province'] ?? ''),
            trim($data['region'] ?? ''),
            trim($data['type'] ?? 'Public'),
            json_encode($data['mention'] ?? []),
            json_encode($data['domaine'] ?? []),
            json_encode($data['parcours'] ?? []),
            json_encode($data['metier'] ?? []),
            json_encode($data['niveau'] ?? []),
            json_encode($data['admission'] ?? []),
            trim($data['contact'] ?? ''),
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = self::getDb();
        $stmt = $pdo->prepare("
            UPDATE etablissements
            SET nom=?, province=?, region=?, type=?, mention=?, domaine=?, parcours=?, metier=?, niveau=?, admission=?, contact=?
            WHERE id = ?
        ");
        return $stmt->execute([
            trim($data['nom']),
            trim($data['province'] ?? ''),
            trim($data['region'] ?? ''),
            trim($data['type'] ?? 'Public'),
            json_encode($data['mention'] ?? []),
            json_encode($data['domaine'] ?? []),
            json_encode($data['parcours'] ?? []),
            json_encode($data['metier'] ?? []),
            json_encode($data['niveau'] ?? []),
            json_encode($data['admission'] ?? []),
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
            WHERE nom LIKE ? OR province LIKE ? OR region LIKE ? OR mention LIKE ? OR metier LIKE ?
            ORDER BY nom ASC
        ");
        $stmt->execute([$like, $like, $like, $like, $like]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([self::class, 'formatEtablissement'], $results);
    }
}
