<?php

require_once __DIR__ . '/../models/Metier.php';
require_once __DIR__ . '/../core/Response.php';

class MetierController
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
        $metiers = $search ? Metier::search($search) : Metier::findAll();
        Response::json(['metiers' => $metiers]);
    }

    public static function show(int $id): void
    {
        $metier = Metier::findById($id);
        if (!$metier) {
            Response::json(['message' => 'Métier introuvable'], 404);
        }
        Response::json(['metier' => $metier]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();

        $errors = [];

        if (empty(trim($data['label'] ?? ''))) {
            $errors['label'] = 'Le libellé est obligatoire';
        }

        if (empty(trim($data['description'] ?? ''))) {
            $errors['description'] = 'La description est obligatoire';
        }

        if (empty($data['parcours']) || !is_array($data['parcours']) || count($data['parcours']) === 0) {
            $errors['parcours'] = 'Au moins un parcours est obligatoire';
        }

        if (empty(trim($data['mention'] ?? ''))) {
            $errors['mention'] = 'La mention est obligatoire';
        }

        if (empty($data['serie']) || !is_array($data['serie']) || count($data['serie']) === 0) {
            $errors['serie'] = 'Au moins une série est obligatoire';
        }

        if (empty(trim($data['niveau'] ?? ''))) {
            $errors['niveau'] = 'Le niveau est obligatoire';
        }

        if (empty($data['parcoursFormation']) || !is_array($data['parcoursFormation']) || count($data['parcoursFormation']) === 0) {
            $errors['parcoursFormation'] = 'Au moins un parcours de formation est obligatoire';
        }

        if (!empty($errors)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors'  => $errors
            ], 422);
        }

        $id = Metier::create($data);
        $metier = Metier::findById($id);
        Response::json([
            'message' => 'Métier créé avec succès',
            'metier'  => $metier
        ], 201);
    }

    public static function update(int $id): void
    {
        $metier = Metier::findById($id);
        if (!$metier) {
            Response::json(['message' => 'Métier introuvable'], 404);
        }

        $data = self::getRequestData();

        $errors = [];

        if (empty(trim($data['label'] ?? ''))) {
            $errors['label'] = 'Le libellé est obligatoire';
        }

        if (empty(trim($data['description'] ?? ''))) {
            $errors['description'] = 'La description est obligatoire';
        }

        if (empty($data['parcours']) || !is_array($data['parcours']) || count($data['parcours']) === 0) {
            $errors['parcours'] = 'Au moins un parcours est obligatoire';
        }

        if (empty(trim($data['mention'] ?? ''))) {
            $errors['mention'] = 'La mention est obligatoire';
        }

        if (empty($data['serie']) || !is_array($data['serie']) || count($data['serie']) === 0) {
            $errors['serie'] = 'Au moins une série est obligatoire';
        }

        if (empty(trim($data['niveau'] ?? ''))) {
            $errors['niveau'] = 'Le niveau est obligatoire';
        }

        if (empty($data['parcoursFormation']) || !is_array($data['parcoursFormation']) || count($data['parcoursFormation']) === 0) {
            $errors['parcoursFormation'] = 'Au moins un parcours de formation est obligatoire';
        }

        if (!empty($errors)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors'  => $errors
            ], 422);
        }

        Metier::update($id, $data);
        Response::json([
            'message' => 'Métier mis à jour avec succès',
            'metier'  => Metier::findById($id)
        ]);
    }

    public static function destroy(int $id): void
    {
        $metier = Metier::findById($id);
        if (!$metier) {
            Response::json(['message' => 'Métier introuvable'], 404);
        }

        Metier::delete($id);
        Response::json(['message' => 'Métier supprimé avec succès']);
    }
}
