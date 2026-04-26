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

        if (empty(trim($data['description'] ?? ''))) {
            $errors['description'] = 'La description est obligatoire';
        } elseif (strlen(trim($data['description'])) < 50) {
            $errors['description'] = 'La description doit faire au moins 50 caractères';
        } elseif (strlen(trim($data['description'])) > 220) {
            $errors['description'] = 'La description ne doit pas dépasser 220 caractères';
        }

        if (empty(trim($data['email'] ?? ''))) {
            $errors['email'] = "L'email est obligatoire";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'email n'est pas valide";
        }

        if (empty($data['mention']) || !is_array($data['mention']) || count($data['mention']) === 0) {
            $errors['mention'] = 'Au moins une mention est obligatoire';
        }

        if (empty($data['domaine']) || !is_array($data['domaine']) || count($data['domaine']) === 0) {
            $errors['domaine'] = 'Au moins un domaine est obligatoire';
        }

        if (empty($data['parcours']) || !is_array($data['parcours']) || count($data['parcours']) === 0) {
            $errors['parcours'] = 'Au moins un parcours est obligatoire';
        }

        if (empty($data['metier']) || !is_array($data['metier']) || count($data['metier']) === 0) {
            $errors['metier'] = 'Au moins un métier est obligatoire';
        }

        if (empty($data['niveau']) || !is_array($data['niveau']) || count($data['niveau']) === 0) {
            $errors['niveau'] = 'Au moins un niveau est obligatoire';
        }

        if (empty($data['admission']) || !is_array($data['admission']) || count($data['admission']) === 0) {
            $errors['admission'] = "Au moins un mode d'admission est obligatoire";
        }

        if (empty($data['contact']) || !is_array($data['contact']) || count($data['contact']) === 0) {
            $errors['contact'] = 'Au moins un contact est obligatoire';
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
