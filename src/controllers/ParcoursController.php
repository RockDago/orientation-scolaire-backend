<?php

require_once __DIR__ . '/../models/Parcours.php';
require_once __DIR__ . '/../core/Response.php';

class ParcoursController
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
        $parcours = $search ? Parcours::search($search) : Parcours::findAll();
        Response::json(['parcours' => $parcours]);
    }

    public static function show(int $id): void
    {
        $parcours = Parcours::findById($id);
        if (!$parcours) {
            Response::json(['message' => 'Parcours introuvable'], 404);
        }
        Response::json(['parcours' => $parcours]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();

        $errors = [];

        if (empty(trim($data['label'] ?? ''))) {
            $errors['label'] = 'Le libellé du parcours est obligatoire';
        }

        if (empty(trim($data['mention'] ?? ''))) {
            $errors['mention'] = 'La mention est obligatoire';
        }

        if (!empty($errors)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors'  => $errors
            ], 422);
        }

        $id = Parcours::create($data);
        $parcours = Parcours::findById($id);
        Response::json([
            'message' => 'Parcours créé avec succès',
            'parcours' => $parcours
        ], 201);
    }

    public static function update(int $id): void
    {
        $parcours = Parcours::findById($id);
        if (!$parcours) {
            Response::json(['message' => 'Parcours introuvable'], 404);
        }

        $data = self::getRequestData();

        $errors = [];

        if (empty(trim($data['label'] ?? ''))) {
            $errors['label'] = 'Le libellé du parcours est obligatoire';
        }

        if (empty(trim($data['mention'] ?? ''))) {
            $errors['mention'] = 'La mention est obligatoire';
        }

        if (!empty($errors)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors'  => $errors
            ], 422);
        }

        Parcours::update($id, $data);
        Response::json([
            'message' => 'Parcours mis à jour avec succès',
            'parcours' => Parcours::findById($id)
        ]);
    }

    public static function destroy(int $id): void
    {
        $parcours = Parcours::findById($id);
        if (!$parcours) {
            Response::json(['message' => 'Parcours introuvable'], 404);
        }

        Parcours::delete($id);
        Response::json(['message' => 'Parcours supprimé avec succès']);
    }
}
