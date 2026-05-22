<?php

require_once __DIR__ . '/../models/Domaine.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/ApiHelpers.php';

class DomaineController
{
    private static function getRequestData(): array
    {
        return ApiHelpers::requestData();
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

        return $errors;
    }

    public static function index(): void
    {
        $domaines = ApiHelpers::filterItems(Domaine::findAll(), []);
        Response::json(ApiHelpers::listResponse('domaines', $domaines));
    }

    public static function show(int $id): void
    {
        $domaine = Domaine::findById($id);
        if (!$domaine) {
            Response::json(['message' => 'Domaine introuvable'], 404);
        }

        Response::json(['domaine' => $domaine, 'data' => $domaine]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        if (Domaine::findByLabel(trim((string) $data['label']))) {
            Response::json(['message' => 'Ce domaine existe deja'], 409);
        }

        $id = Domaine::create($data);
        $domaine = Domaine::findById($id);
        Response::json([
            'message' => 'Domaine cree avec succes',
            'domaine' => $domaine,
            'data' => $domaine,
        ], 201);
    }

    public static function update(int $id): void
    {
        $domaine = Domaine::findById($id);
        if (!$domaine) {
            Response::json(['message' => 'Domaine introuvable'], 404);
        }

        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $label = trim((string) $data['label']);
        if ($label !== $domaine['label'] && Domaine::findByLabel($label)) {
            Response::json(['message' => 'Ce libelle de domaine existe deja'], 409);
        }

        Domaine::update($id, $data);
        $updated = Domaine::findById($id);
        Response::json([
            'message' => 'Domaine mis a jour avec succes',
            'domaine' => $updated,
            'data' => $updated,
        ]);
    }

    public static function destroy(int $id): void
    {
        $domaine = Domaine::findById($id);
        if (!$domaine) {
            Response::json(['message' => 'Domaine introuvable'], 404);
        }

        Domaine::delete($id);
        Response::json([
            'message' => 'Domaine supprime avec succes',
            'domaine' => $domaine,
            'data' => $domaine,
        ]);
    }
}
