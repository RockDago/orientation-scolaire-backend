<?php

require_once __DIR__ . '/../models/Domaine.php';
require_once __DIR__ . '/../core/Response.php';

class DomaineController
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
        $domaines = $search ? Domaine::search($search) : Domaine::findAll();
        Response::json(['domaines' => $domaines]);
    }

    public static function show(int $id): void
    {
        $domaine = Domaine::findById($id);
        if (!$domaine) {
            Response::json(['message' => 'Domaine introuvable'], 404);
        }
        Response::json(['domaine' => $domaine]);
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

        if (Domaine::findByLabel($data['label'])) {
            Response::json(['message' => 'Ce domaine existe déjà'], 409);
        }

        $id = Domaine::create($data);
        $domaine = Domaine::findById($id);
        Response::json(['message' => 'Domaine créé avec succès', 'domaine' => $domaine], 201);
    }

    public static function update(int $id): void
    {
        $domaine = Domaine::findById($id);
        if (!$domaine) {
            Response::json(['message' => 'Domaine introuvable'], 404);
        }

        $data = self::getRequestData();

        if (empty(trim($data['label'] ?? ''))) {
            Response::json(['message' => 'Le libellé est obligatoire'], 422);
        }

        if (empty(trim($data['description'] ?? ''))) {
            Response::json(['message' => 'La description est obligatoire'], 422);
        }

        if ($data['label'] !== $domaine['label'] && Domaine::findByLabel($data['label'])) {
            Response::json(['message' => 'Ce libellé de domaine existe déjà'], 409);
        }

        Domaine::update($id, $data);
        Response::json([
            'message' => 'Domaine mis à jour avec succès',
            'domaine' => Domaine::findById($id)
        ]);
    }

    public static function destroy(int $id): void
    {
        $domaine = Domaine::findById($id);
        if (!$domaine) {
            Response::json(['message' => 'Domaine introuvable'], 404);
        }

        Domaine::delete($id);
        Response::json(['message' => 'Domaine supprimé avec succès']);
    }
}
