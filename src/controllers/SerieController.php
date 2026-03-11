<?php
// controllers/SerieController.php

require_once __DIR__ . '/../models/Serie.php';
require_once __DIR__ . '/../core/Response.php';

class SerieController
{
    private static function getRequestData(): array
    {
        $data = $_POST ?: [];
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) $data = array_merge($data, $json);
        return $data;
    }

    public static function index(): void
    {
        $search = $_GET['search'] ?? '';
        $series = $search ? Serie::search($search) : Serie::findAll();
        Response::json(['series' => $series]);
    }

    public static function show(int $id): void
    {
        $serie = Serie::findById($id);
        if (!$serie) {
            Response::json(['message' => 'Série introuvable'], 404);
        }
        Response::json(['serie' => $serie]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();

        if (empty(trim($data['code'] ?? '')) || empty(trim($data['label'] ?? ''))) {
            Response::json(['message' => 'Le code et le libellé sont obligatoires'], 422);
        }

        if (Serie::findByCode($data['code'])) {
            Response::json(['message' => 'Ce code série existe déjà'], 409);
        }

        $id = Serie::create($data);
        $serie = Serie::findById($id);
        Response::json(['message' => 'Série créée avec succès', 'serie' => $serie], 201);
    }

    public static function update(int $id): void
    {
        $serie = Serie::findById($id);
        if (!$serie) {
            Response::json(['message' => 'Série introuvable'], 404);
        }

        $data = self::getRequestData();

        if (empty(trim($data['code'] ?? '')) || empty(trim($data['label'] ?? ''))) {
            Response::json(['message' => 'Le code et le libellé sont obligatoires'], 422);
        }

        Serie::update($id, $data);
        Response::json(['message' => 'Série mise à jour', 'serie' => Serie::findById($id)]);
    }

    public static function destroy(int $id): void
    {
        $serie = Serie::findById($id);
        if (!$serie) {
            Response::json(['message' => 'Série introuvable'], 404);
        }
        Serie::delete($id);
        Response::json(['message' => 'Série supprimée avec succès']);
    }
}
