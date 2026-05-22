<?php

require_once __DIR__ . '/../models/Etablissement.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/ApiHelpers.php';

class EtablissementController
{
    private static function getRequestData(): array
    {
        return ApiHelpers::requestData();
    }

    private static function validate(array $data): array
    {
        $errors = [];

        if (empty(trim((string) ($data['nom'] ?? '')))) {
            $errors['nom'] = "Le nom de l'etablissement est obligatoire";
        }

        if (empty(trim((string) ($data['province'] ?? '')))) {
            $errors['province'] = 'La province est obligatoire';
        }

        if (empty(trim((string) ($data['region'] ?? '')))) {
            $errors['region'] = 'La region est obligatoire';
        }

        if (empty(trim((string) ($data['type'] ?? '')))) {
            $errors['type'] = 'Le type est obligatoire';
        } elseif (!in_array($data['type'], ['Public', 'Prive', 'Privé'], true)) {
            $errors['type'] = "Le type doit etre Public ou Prive";
        }

        $description = trim((string) ($data['description'] ?? ''));
        if ($description === '') {
            $errors['description'] = 'La description est obligatoire';
        } elseif (strlen($description) < 50) {
            $errors['description'] = 'La description doit faire au moins 50 caracteres';
        } elseif (strlen($description) > 220) {
            $errors['description'] = 'La description ne doit pas depasser 220 caracteres';
        }

        if (empty(trim((string) ($data['email'] ?? '')))) {
            $errors['email'] = "L'email est obligatoire";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'email n'est pas valide";
        }

        foreach ([
            'mention' => 'Au moins une mention est obligatoire',
            'domaine' => 'Au moins un domaine est obligatoire',
            'parcours' => 'Au moins un parcours est obligatoire',
            'metier' => 'Au moins un metier est obligatoire',
            'niveau' => 'Au moins un niveau est obligatoire',
            'admission' => "Au moins un mode d'admission est obligatoire",
            'contact' => 'Au moins un contact est obligatoire',
        ] as $field => $message) {
            if (empty($data[$field]) || !is_array($data[$field])) {
                $errors[$field] = $message;
            }
        }

        return $errors;
    }

    public static function index(): void
    {
        $etablissements = ApiHelpers::filterItems(Etablissement::findAll(), [
            'region' => 'region',
            'province' => 'province',
            'mention' => 'mention',
            'domaine' => 'domaine',
            'metier' => 'metier',
            'niveau' => 'niveau',
            'type' => 'type',
        ]);

        Response::json(ApiHelpers::listResponse('etablissements', $etablissements));
    }

    public static function show(int $id): void
    {
        $etablissement = Etablissement::findById($id);
        if (!$etablissement) {
            Response::json(['message' => 'Etablissement introuvable'], 404);
        }

        Response::json(['etablissement' => $etablissement, 'data' => $etablissement]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $id = Etablissement::create($data);
        $etablissement = Etablissement::findById($id);
        Response::json([
            'message' => 'Etablissement cree avec succes',
            'etablissement' => $etablissement,
            'data' => $etablissement,
        ], 201);
    }

    public static function update(int $id): void
    {
        $etablissement = Etablissement::findById($id);
        if (!$etablissement) {
            Response::json(['message' => 'Etablissement introuvable'], 404);
        }

        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        Etablissement::update($id, $data);
        $updated = Etablissement::findById($id);
        Response::json([
            'message' => 'Etablissement mis a jour avec succes',
            'etablissement' => $updated,
            'data' => $updated,
        ]);
    }

    public static function destroy(int $id): void
    {
        $etablissement = Etablissement::findById($id);
        if (!$etablissement) {
            Response::json(['message' => 'Etablissement introuvable'], 404);
        }

        Etablissement::delete($id);
        Response::json([
            'message' => 'Etablissement supprime avec succes',
            'etablissement' => $etablissement,
            'data' => $etablissement,
        ]);
    }
}
