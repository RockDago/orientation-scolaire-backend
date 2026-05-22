<?php

require_once __DIR__ . '/../models/Metier.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/ApiHelpers.php';

class MetierController
{
    private static function getRequestData(): array
    {
        return ApiHelpers::requestData();
    }

    private static function normalizeArrayFields(array &$data): void
    {
        foreach (['parcours', 'serie', 'parcoursFormation', 'mention', 'domaine', 'niveau'] as $field) {
            if (!isset($data[$field]) || !is_array($data[$field])) {
                $data[$field] = [];
            }
        }
    }

    private static function validate(array $data): array
    {
        $errors = [];

        if (empty(trim((string) ($data['label'] ?? '')))) {
            $errors['label'] = 'Le libelle est obligatoire';
        }

        if (empty(trim((string) ($data['description'] ?? '')))) {
            $errors['description'] = 'La description est obligatoire';
        }

        foreach ([
            'parcours' => 'Au moins un parcours est obligatoire',
            'mention' => 'Au moins une mention est obligatoire',
            'domaine' => 'Au moins un domaine est obligatoire',
            'serie' => 'Au moins une serie est obligatoire',
            'niveau' => 'Au moins un niveau est obligatoire',
            'parcoursFormation' => 'Au moins un parcours de formation est obligatoire',
        ] as $field => $message) {
            if (empty($data[$field]) || !is_array($data[$field])) {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    public static function index(): void
    {
        $metiers = ApiHelpers::filterItems(Metier::findAll(), [
            'mention' => 'mention',
            'domaine' => 'domaine',
            'niveau' => 'niveau',
            'serie' => 'serie',
            'type' => 'niveau',
        ]);
        Response::json(ApiHelpers::listResponse('metiers', $metiers));
    }

    public static function show(int $id): void
    {
        $metier = Metier::findById($id);
        if (!$metier) {
            Response::json(['message' => 'Metier introuvable'], 404);
        }

        Response::json(['metier' => $metier, 'data' => $metier]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();
        self::normalizeArrayFields($data);
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $id = Metier::create($data);
        $metier = Metier::findById($id);
        Response::json([
            'message' => 'Metier cree avec succes',
            'metier' => $metier,
            'data' => $metier,
        ], 201);
    }

    public static function update(int $id): void
    {
        $metier = Metier::findById($id);
        if (!$metier) {
            Response::json(['message' => 'Metier introuvable'], 404);
        }

        $data = self::getRequestData();
        self::normalizeArrayFields($data);
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        Metier::update($id, $data);
        $updated = Metier::findById($id);
        Response::json([
            'message' => 'Metier mis a jour avec succes',
            'metier' => $updated,
            'data' => $updated,
        ]);
    }

    public static function destroy(int $id): void
    {
        $metier = Metier::findById($id);
        if (!$metier) {
            Response::json(['message' => 'Metier introuvable'], 404);
        }

        Metier::delete($id);
        Response::json([
            'message' => 'Metier supprime avec succes',
            'metier' => $metier,
            'data' => $metier,
        ]);
    }
}
