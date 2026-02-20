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

/*
1️⃣ Normal test
http://localhost:8080
2️⃣ CPU stress test
http://localhost:8080?cpu=1
3️⃣ DB stress test
http://localhost:8080?db=1
*/