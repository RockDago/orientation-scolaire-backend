<?php

require_once __DIR__ . '/../models/Mention.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/ApiHelpers.php';

class MentionController
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

        if (empty($data['domaine_id'])) {
            $errors['domaine_id'] = 'Le domaine est obligatoire';
        }

        if (empty(trim((string) ($data['description'] ?? '')))) {
            $errors['description'] = 'La description est obligatoire';
        }

        return $errors;
    }

    public static function index(): void
    {
        $mentions = ApiHelpers::filterItems(Mention::findAll(), [
            'domaine' => 'domaine_label',
            'domaine_id' => 'domaine_id',
        ]);
        Response::json(ApiHelpers::listResponse('mentions', $mentions));
    }

    public static function show(int $id): void
    {
        $mention = Mention::findById($id);
        if (!$mention) {
            Response::json(['message' => 'Mention introuvable'], 404);
        }

        Response::json(['mention' => $mention, 'data' => $mention]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        if (Mention::findByLabel(trim((string) $data['label']))) {
            Response::json(['message' => 'Cette mention existe deja'], 409);
        }

        $id = Mention::create($data);
        $mention = Mention::findById($id);
        Response::json([
            'message' => 'Mention creee avec succes',
            'mention' => $mention,
            'data' => $mention,
        ], 201);
    }

    public static function update(int $id): void
    {
        $mention = Mention::findById($id);
        if (!$mention) {
            Response::json(['message' => 'Mention introuvable'], 404);
        }

        $data = self::getRequestData();
        $errors = self::validate($data);

        if (!empty($errors)) {
            Response::json(['message' => 'Erreurs de validation', 'errors' => $errors], 422);
        }

        $label = trim((string) $data['label']);
        if ($label !== $mention['label'] && Mention::findByLabel($label)) {
            Response::json(['message' => 'Ce libelle de mention existe deja'], 409);
        }

        Mention::update($id, $data);
        $updated = Mention::findById($id);
        Response::json([
            'message' => 'Mention mise a jour avec succes',
            'mention' => $updated,
            'data' => $updated,
        ]);
    }

    public static function destroy(int $id): void
    {
        $mention = Mention::findById($id);
        if (!$mention) {
            Response::json(['message' => 'Mention introuvable'], 404);
        }

        Mention::delete($id);
        Response::json([
            'message' => 'Mention supprimee avec succes',
            'mention' => $mention,
            'data' => $mention,
        ]);
    }
}
