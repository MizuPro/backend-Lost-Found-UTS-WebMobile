<?php

// ── Bootstrap ─────────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/Database.php';

// Helpers
require_once __DIR__ . '/helpers/ResponseHelper.php';
require_once __DIR__ . '/helpers/JwtHelper.php';
require_once __DIR__ . '/helpers/ValidationHelper.php';

// Middleware
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/RoleMiddleware.php';

// Models
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/FoundItemModel.php';
require_once __DIR__ . '/models/LostReportModel.php';

// Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/FoundItemController.php';
require_once __DIR__ . '/controllers/LostReportController.php';

// ── CORS Headers ──────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Tangani preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Router ────────────────────────────────────────────────────────────────────
$routes = require_once __DIR__ . '/routes/api.php';

$method     = $_SERVER['REQUEST_METHOD'];

// Method Spoofing untuk PUT/PATCH/DELETE via POST (misal dari form-data)
if ($method === 'POST' && isset($_POST['_method'])) {
    $spoofedMethod = strtoupper($_POST['_method']);
    if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'], true)) {
        $method = $spoofedMethod;
    }
}

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip base path agar routing bekerja di subdirektori (misal: /backend-Lost-Found)
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Normalisasi URI: hapus trailing slash kecuali root "/"
$requestUri = rtrim($requestUri, '/');
if ($requestUri === '') {
    $requestUri = '/';
}

// ── Handle root "/" → info API ────────────────────────────────────────────────
if ($requestUri === '/' && $method === 'GET') {
    ResponseHelper::success([
        'app'     => APP_NAME,
        'version' => APP_VERSION,
        'status'  => 'running',
    ], 'API is running.');
    exit;
}

// Cari route yang cocok
$matched     = null;
$routeParams = [];

foreach ($routes as $routeKey => $handler) {
    $parts        = explode(' ', $routeKey, 2);
    $routeMethod  = $parts[0];
    $routePattern = $parts[1];

    if (strtoupper($routeMethod) !== strtoupper($method)) {
        continue;
    }

    // Konversi pattern {param} ke regex
    $regexPattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $routePattern);
    $regexPattern = '#^' . $regexPattern . '$#';

    if (preg_match($regexPattern, $requestUri, $matches)) {
        // Ambil nama parameter dari pattern
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $routePattern, $paramNames);

        foreach ($paramNames[1] as $index => $paramName) {
            $routeParams[$paramName] = $matches[$index + 1];
        }

        $matched = $handler;
        break;
    }
}

if (!$matched) {
    ResponseHelper::notFound('Endpoint tidak ditemukan: ' . $method . ' ' . $requestUri);
}

$controllerClass  = $matched[0];
$controllerMethod = $matched[1];
$middlewares      = isset($matched[2]) ? (array) $matched[2] : [];

// ── Jalankan Middleware ───────────────────────────────────────────────────────
foreach ($middlewares as $mw) {
    if ($mw === 'auth') {
        AuthMiddleware::handle();
        continue;
    }

    if (substr($mw, 0, 5) === 'role:') {
        $roles = explode(',', substr($mw, 5));
        RoleMiddleware::handle($roles);
        continue;
    }
}

// ── Dispatch ke Controller ────────────────────────────────────────────────────
// Auto-load controller jika belum di-require
$controllerFile = __DIR__ . '/controllers/' . $controllerClass . '.php';
if (!class_exists($controllerClass) && file_exists($controllerFile)) {
    require_once $controllerFile;
}

if (!class_exists($controllerClass)) {
    ResponseHelper::error('Controller tidak ditemukan: ' . $controllerClass, 500);
}

$controller = new $controllerClass();

if (!method_exists($controller, $controllerMethod)) {
    ResponseHelper::error('Method tidak ditemukan: ' . $controllerMethod, 500);
}

// Kirim route params ke controller via GLOBALS agar bisa diakses di semua metode
$GLOBALS['route_params'] = $routeParams;

$controller->$controllerMethod();

