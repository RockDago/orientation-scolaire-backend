<?php

require_once __DIR__ . '/../models/Etablissement.php';
require_once __DIR__ . '/../core/Response.php';

class EtablissementController
{
    private static function getRequestData(): array
    {
        $data = $_POST ?: [];
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) $data = array_merge($data, $json);
        return $data;
    }

    private static function validate(array $data): array
    {
        $errors = [];

        if (empty(trim($data['nom'] ?? ''))) {
            $errors['nom'] = "Le nom de l'établissement est obligatoire";
        }

        if (empty(trim($data['province'] ?? ''))) {
            $errors['province'] = 'La province est obligatoire';
        }

        if (empty(trim($data['region'] ?? ''))) {
            $errors['region'] = 'La région est obligatoire';
        }

        if (empty(trim($data['type'] ?? ''))) {
            $errors['type'] = 'Le type est obligatoire';
        } elseif (!in_array($data['type'], ['Public', 'Privé'])) {
            $errors['type'] = "Le type doit être 'Public' ou 'Privé'";
        }

        if (empty(trim($data['mention'] ?? ''))) {
            $errors['mention'] = 'La mention est obligatoire';
        }

        if (empty(trim($data['parcours'] ?? ''))) {
            $errors['parcours'] = 'Le parcours est obligatoire';
        }

        if (empty(trim($data['metier'] ?? ''))) {
            $errors['metier'] = 'Le métier est obligatoire';
        }

        if (empty(trim($data['niveau'] ?? ''))) {
            $errors['niveau'] = 'Le niveau est obligatoire';
        }

        if (empty(trim($data['duree'] ?? ''))) {
            $errors['duree'] = 'La durée est obligatoire';
        }

        if (empty(trim($data['admission'] ?? ''))) {
            $errors['admission'] = "Le mode d'admission est obligatoire";
        }

        if (empty(trim($data['contact'] ?? ''))) {
            $errors['contact'] = 'Le contact est obligatoire';
        }

        return $errors;
    }

    public static function index(): void
    {
        $search = $_GET['search'] ?? '';
        $etablissements = $search
            ? Etablissement::search($search)
            : Etablissement::findAll();

        Response::json(['etablissements' => $etablissements]);
    }

    public static function show(int $id): void
    {
        $etablissement = Etablissement::findById($id);
        if (!$etablissement) {
            Response::json(['message' => 'Établissement introuvable'], 404);
        }
        Response::json(['etablissement' => $etablissement]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors'  => $errors
            ], 422);
        }

        $id = Etablissement::create($data);
        $etablissement = Etablissement::findById($id);
        Response::json([
            'message'        => 'Établissement créé avec succès',
            'etablissement'  => $etablissement
        ], 201);
    }

    public static function update(int $id): void
    {
        $etablissement = Etablissement::findById($id);
        if (!$etablissement) {
            Response::json(['message' => 'Établissement introuvable'], 404);
        }

        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json([
                'message' => 'Erreurs de validation',
                'errors'  => $errors
            ], 422);
        }

        Etablissement::update($id, $data);
        Response::json([
            'message'       => 'Établissement mis à jour avec succès',
            'etablissement' => Etablissement::findById($id)
        ]);
    }

    public static function destroy(int $id): void
    {
        $etablissement = Etablissement::findById($id);
        if (!$etablissement) {
            Response::json(['message' => 'Établissement introuvable'], 404);
        }

        Etablissement::delete($id);
        Response::json(['message' => 'Établissement supprimé avec succès']);
    }
}
