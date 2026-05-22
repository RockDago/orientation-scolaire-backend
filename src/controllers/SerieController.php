<?php

require_once __DIR__ . '/../models/Serie.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/ApiHelpers.php';

class SerieController
{
    private static function getRequestData(): array
    {
        return ApiHelpers::requestData();
    }

    private static function validate(array $data): array
    {
        $errors = [];

        if (empty(trim((string) ($data['code'] ?? '')))) {
            $errors['code'] = 'Le code est obligatoire';
        }

        if (empty(trim((string) ($data['label'] ?? '')))) {
            $errors['label'] = 'Le libelle est obligatoire';
        }

        return $errors;
    }

    public static function index(): void
    {
        $series = ApiHelpers::filterItems(Serie::findAll(), []);
        Response::json(ApiHelpers::listResponse('series', $series));
    }

    public static function show(int $id): void
    {
        $serie = Serie::findById($id);
        if (!$serie) {
            Response::json(['message' => 'Serie introuvable'], 404);
        }

        Response::json(['serie' => $serie, 'data' => $serie]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        if (Serie::findByCode(trim((string) $data['code']))) {
            Response::json(['message' => 'Ce code serie existe deja'], 409);
        }

        $id = Serie::create($data);
        $serie = Serie::findById($id);
        Response::json([
            'message' => 'Serie creee avec succes',
            'serie' => $serie,
            'data' => $serie,
        ], 201);
    }

    public static function update(int $id): void
    {
        $serie = Serie::findById($id);
        if (!$serie) {
            Response::json(['message' => 'Serie introuvable'], 404);
        }

        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $existing = Serie::findByCode(trim((string) $data['code']));
        if ($existing && (int) $existing['id'] !== $id) {
            Response::json(['message' => 'Ce code serie existe deja'], 409);
        }

        Serie::update($id, $data);
        $updated = Serie::findById($id);
        Response::json([
            'message' => 'Serie mise a jour avec succes',
            'serie' => $updated,
            'data' => $updated,
        ]);
    }

    public static function destroy(int $id): void
    {
        $serie = Serie::findById($id);
        if (!$serie) {
            Response::json(['message' => 'Serie introuvable'], 404);
        }

        Serie::delete($id);
        Response::json([
            'message' => 'Serie supprimee avec succes',
            'serie' => $serie,
            'data' => $serie,
        ]);
    }
}
