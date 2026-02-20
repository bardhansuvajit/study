<?php

declare(strict_types=1);

header('Content-Type: application/json');

$start = microtime(true);

/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/
$config = [
    'host' => 'db',
    'dbname' => 'testdb',
    'user' => 'testuser',
    'pass' => 'secret',
];

/*
|--------------------------------------------------------------------------
| Database Connection (Production Ready)
|--------------------------------------------------------------------------
*/
try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => true, // connection reuse
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

/*
|--------------------------------------------------------------------------
| Basic Router
|--------------------------------------------------------------------------
*/
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

/*
|--------------------------------------------------------------------------
| Routes
|--------------------------------------------------------------------------
*/

// GET /users?page=1&limit=10
if ($method === 'GET' && $uri === '/users') {

    $page  = max((int)($_GET['page'] ?? 1), 1);
    $limit = min(max((int)($_GET['limit'] ?? 10), 1), 100);
    $offset = ($page - 1) * $limit;

    $stmt = $pdo->prepare("SELECT id, name, email FROM users LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $users = $stmt->fetchAll();

    echo json_encode([
        'data' => $users,
        'page' => $page,
        'limit' => $limit
    ]);
    exit;
}

// POST /users
if ($method === 'POST' && $uri === '/users') {

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['name'], $input['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (:name, :email)");
    $stmt->execute([
        ':name' => $input['name'],
        ':email' => $input['email'],
    ]);

    http_response_code(201);
    echo json_encode(['message' => 'User created']);
    exit;
}

/*
|--------------------------------------------------------------------------
| 404
|--------------------------------------------------------------------------
*/
http_response_code(404);
echo json_encode(['error' => 'Route not found']);

/*
|--------------------------------------------------------------------------
| Performance Footer (for benchmarking)
|--------------------------------------------------------------------------
*/
$end = microtime(true);
error_log("Request time: " . round(($end - $start) * 1000, 2) . " ms");