<?php

require_once __DIR__ . '/../models/Mention.php';
require_once __DIR__ . '/../core/Response.php';

class MentionController
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
        $mentions = $search ? Mention::search($search) : Mention::findAll();
        Response::json(['mentions' => $mentions]);
    }

    public static function show(int $id): void
    {
        $mention = Mention::findById($id);
        if (!$mention) {
            Response::json(['message' => 'Mention introuvable'], 404);
        }
        Response::json(['mention' => $mention]);
    }

    public static function store(): void
    {
        $data = self::getRequestData();

        if (empty(trim($data['label'] ?? ''))) {
            Response::json(['message' => 'Le libellé est obligatoire'], 422);
        }

        if (empty(trim($data['description'] ?? ''))) {
            Response::json(['message' => 'La description est obligatoire'], 422);
        }

        if (Mention::findByLabel($data['label'])) {
            Response::json(['message' => 'Cette mention existe déjà'], 409);
        }

        $id = Mention::create($data);
        $mention = Mention::findById($id);
        Response::json(['message' => 'Mention créée avec succès', 'mention' => $mention], 201);
    }

    public static function update(int $id): void
    {
        $mention = Mention::findById($id);
        if (!$mention) {
            Response::json(['message' => 'Mention introuvable'], 404);
        }

        $data = self::getRequestData();

        if (empty(trim($data['label'] ?? ''))) {
            Response::json(['message' => 'Le libellé est obligatoire'], 422);
        }

        if (empty(trim($data['description'] ?? ''))) {
            Response::json(['message' => 'La description est obligatoire'], 422);
        }

        if ($data['label'] !== $mention['label'] && Mention::findByLabel($data['label'])) {
            Response::json(['message' => 'Ce libellé de mention existe déjà'], 409);
        }

        Mention::update($id, $data);
        Response::json([
            'message' => 'Mention mise à jour avec succès',
            'mention' => Mention::findById($id)
        ]);
    }

    public static function destroy(int $id): void
    {
        $mention = Mention::findById($id);
        if (!$mention) {
            Response::json(['message' => 'Mention introuvable'], 404);
        }

        Mention::delete($id);
        Response::json(['message' => 'Mention supprimée avec succès']);
    }
}
