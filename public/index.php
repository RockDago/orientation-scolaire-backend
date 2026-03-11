<?php

$allowedOrigins = [
    'http://localhost:5173',
    'http://localhost:3000',
    'http://localhost:8080',
    'http://127.0.0.1:5173',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header('Access-Control-Allow-Origin: http://localhost:5173');
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept, X-Requested-With, Origin');
header('Access-Control-Max-Age: 3600');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/../src/core/Response.php';
require_once __DIR__ . '/../src/core/Jwt.php';
require_once __DIR__ . '/../src/middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/ProfileController.php';
require_once __DIR__ . '/../src/controllers/SerieController.php';
require_once __DIR__ . '/../src/controllers/MentionController.php';
require_once __DIR__ . '/../src/controllers/ParcoursController.php';
require_once __DIR__ . '/../src/controllers/MetierController.php';
require_once __DIR__ . '/../src/controllers/EtablissementController.php';
require_once __DIR__ . '/../src/controllers/DashboardController.php';


$method   = $_SERVER['REQUEST_METHOD'];
$uri      = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri      = str_replace('/orientation-scolaire-professionnelle/backend/public', '', $uri);
$parts    = explode('/', trim($uri, '/'));
$resource = $parts[1] ?? '';
$id       = isset($parts[2]) && is_numeric($parts[2]) ? (int) $parts[2] : null;
$sub      = $parts[2] ?? '';


if ($resource === 'login' && $method === 'POST') {
    AuthController::login();
    exit;
}

if ($resource === 'register' && $method === 'POST') {
    AuthController::register();
    exit;
}


if ($resource === 'track-view' && $method === 'POST') {
    DashboardController::trackView();
    exit;
}

if ($resource === 'track-search' && $method === 'POST') {
    DashboardController::trackSearch();
    exit;
}

if ($resource === 'track-etablissement-selection' && $method === 'POST') {
    DashboardController::trackEtablissementSelection();
    exit;
}

if ($resource === 'top-metiers' && $method === 'GET') {
    DashboardController::getTopMetiers();
    exit;
}


if ($resource === 'metiers' && $method === 'GET') {
    $id !== null
        ? MetierController::show($id)
        : MetierController::index();
    exit;
}


if ($resource === 'mentions' && $method === 'GET') {
    $id !== null
        ? MentionController::show($id)
        : MentionController::index();
    exit;
}


if ($resource === 'series' && $method === 'GET') {
    $id !== null
        ? SerieController::show($id)
        : SerieController::index();
    exit;
}


if ($resource === 'parcours' && $method === 'GET') {
    $id !== null
        ? ParcoursController::show($id)
        : ParcoursController::index();
    exit;
}


if ($resource === 'etablissements' && $method === 'GET') {
    $id !== null
        ? EtablissementController::show($id)
        : EtablissementController::index();
    exit;
}


AuthMiddleware::handle();


if ($resource === 'dashboard' && $method === 'GET') {
    DashboardController::index();
    exit;
}


if ($resource === 'profile') {
    if ($sub === 'change-password' && $method === 'POST') {
        ProfileController::changePassword();
        exit;
    }
    match($method) {
        'GET'   => ProfileController::show(),
        'PUT'   => ProfileController::update(),
        default => Response::json(['message' => 'Méthode non autorisée'], 405),
    };
    exit;
}


if ($resource === 'series') {
    match(true) {
        $method === 'POST'                   => SerieController::store(),
        $method === 'PUT'    && $id !== null => SerieController::update($id),
        $method === 'DELETE' && $id !== null => SerieController::destroy($id),
        default => Response::json(['message' => 'Méthode non autorisée'], 405),
    };
    exit;
}


if ($resource === 'mentions') {
    match(true) {
        $method === 'POST'                   => MentionController::store(),
        $method === 'PUT'    && $id !== null => MentionController::update($id),
        $method === 'DELETE' && $id !== null => MentionController::destroy($id),
        default => Response::json(['message' => 'Méthode non autorisée'], 405),
    };
    exit;
}


if ($resource === 'parcours') {
    match(true) {
        $method === 'POST'                   => ParcoursController::store(),
        $method === 'PUT'    && $id !== null => ParcoursController::update($id),
        $method === 'DELETE' && $id !== null => ParcoursController::destroy($id),
        default => Response::json(['message' => 'Méthode non autorisée'], 405),
    };
    exit;
}


if ($resource === 'metiers') {
    match(true) {
        $method === 'POST'                   => MetierController::store(),
        $method === 'PUT'    && $id !== null => MetierController::update($id),
        $method === 'DELETE' && $id !== null => MetierController::destroy($id),
        default => Response::json(['message' => 'Méthode non autorisée'], 405),
    };
    exit;
}


if ($resource === 'etablissements') {
    match(true) {
        $method === 'POST'                   => EtablissementController::store(),
        $method === 'PUT'    && $id !== null => EtablissementController::update($id),
        $method === 'DELETE' && $id !== null => EtablissementController::destroy($id),
        default => Response::json(['message' => 'Méthode non autorisée'], 405),
    };
    exit;
}

Response::json(['message' => 'Route introuvable'], 404);