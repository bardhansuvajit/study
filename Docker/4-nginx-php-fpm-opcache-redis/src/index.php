<?php

$start = microtime(true);

$host = "db";
$dbname = "testdb";
$user = "testuser";
$pass = "secret";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $user,
        $pass
    );

    echo "✅ Connected to MySQL successfully!<br>";

    // create table once
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL
    )");

    // Insert only if table empty
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO users (name, email) VALUES 
            ('Alice', 'alice@example.com'),
            ('Bob', 'bob@example.com'),
            ('Charlie', 'charlie@example.com')");
    }

    // 🔥 OPTIONAL: Simulate CPU load
    if (isset($_GET['cpu'])) {
        for ($i = 0; $i < 50000000; $i++) {
            sqrt($i);
        }
        echo "CPU stress applied<br>";
    }

    // 🔥 OPTIONAL: Simulate DB load
    if (isset($_GET['db'])) {
        for ($i = 0; $i < 10000; $i++) {
            $pdo->query("SELECT * FROM users")->fetchAll();
        }
        echo "DB stress applied<br>";
    }

    // Normal query
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Users in Database:</h2>";
    foreach ($users as $user) {
        echo "<p>{$user['name']} - {$user['email']}</p>";
    }

} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}

$end = microtime(true);
$executionTime = ($end - $start) * 1000;

echo "<hr>";
echo "<strong>Page generated in: " . round($executionTime, 2) . " ms</strong>";

?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP + Redis Demo</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .nav { background: #333; padding: 10px; margin-bottom: 20px; }
        .nav a { color: white; text-decoration: none; padding: 10px 15px; display: inline-block; }
        .nav a:hover { background: #555; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: #f9f9f9; border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="/">🏠 Home</a>
        <a href="/redis-basic.php">🔴 Redis Basic</a>
        <a href="/redis-cache.php">🗄️ DB Cache</a>
        <a href="/redis-session.php">🔐 Sessions</a>
        <a href="/redis-rate-limit.php">⏱️ Rate Limit</a>
        <a href="/redis-dashboard.php">📊 Dashboard</a>
    </div>
    
    <div class="container">
    </div>
</body>
</html>