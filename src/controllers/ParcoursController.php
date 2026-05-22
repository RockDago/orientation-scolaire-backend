<?php

require_once __DIR__ . '/../models/Parcours.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/ApiHelpers.php';

class ParcoursController
{
    private static function getRequestData(): array
    {
        return ApiHelpers::requestData();
    }

    private static function validate(array $data): array
    {
        $errors = [];

        if (empty(trim((string) ($data['label'] ?? '')))) {
            $errors['label'] = 'Le libelle du parcours est obligatoire';
        }

        if (empty(trim((string) ($data['mention'] ?? '')))) {
            $errors['mention'] = 'La mention est obligatoire';
        }

        if (empty($data['niveau']) || !is_array($data['niveau'])) {
            $errors['niveau'] = 'Au moins un niveau est obligatoire';
        }

        if (empty($data['duree']) || !is_array($data['duree'])) {
            $errors['duree'] = 'Au moins une duree est obligatoire';
        }

        return $errors;
    }

    public static function index(): void
    {
        $parcours = ApiHelpers::filterItems(Parcours::findAll(), [
            'mention' => 'mention',
            'niveau' => 'niveau',
            'type' => 'niveau',
        ]);
        Response::json(ApiHelpers::listResponse('parcours', $parcours));
    }

    public static function show(int $id): void
    {
        $parcours = Parcours::findById($id);
        if (!$parcours) {
            Response::json(['message' => 'Parcours introuvable'], 404);
        }

        Response::json(['parcours' => $parcours, 'data' => $parcours]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $id = Parcours::create($data);
        $parcours = Parcours::findById($id);
        Response::json([
            'message' => 'Parcours cree avec succes',
            'parcours' => $parcours,
            'data' => $parcours,
        ], 201);
    }

    public static function update(int $id): void
    {
        $parcours = Parcours::findById($id);
        if (!$parcours) {
            Response::json(['message' => 'Parcours introuvable'], 404);
        }

        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        Parcours::update($id, $data);
        $updated = Parcours::findById($id);
        Response::json([
            'message' => 'Parcours mis a jour avec succes',
            'parcours' => $updated,
            'data' => $updated,
        ]);
    }

    public static function destroy(int $id): void
    {
        $parcours = Parcours::findById($id);
        if (!$parcours) {
            Response::json(['message' => 'Parcours introuvable'], 404);
        }

        Parcours::delete($id);
        Response::json([
            'message' => 'Parcours supprime avec succes',
            'parcours' => $parcours,
            'data' => $parcours,
        ]);
    }
}
