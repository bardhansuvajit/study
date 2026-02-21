<?php
// redis-cache.php - Cache database queries with Redis
$start = microtime(true);

// Connect to Redis
$redis = new Redis();
$redis->connect('redis', 6379);
$redis->select(1); // Use database 1 for caching

// Connect to MySQL
$host = "db";
$dbname = "testdb";
$user = "testuser";
$pass = "secret";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    
    echo "<h1>🗄️ Database Query Caching with Redis</h1>";
    echo "<style>
        body { font-family: Arial; padding: 20px; }
        .cache-hit { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; }
        .cache-miss { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; }
        .stats { background: #e9ecef; padding: 15px; border-radius: 5px; }
        .btn { display: inline-block; padding: 8px 15px; margin: 5px; text-decoration: none; border-radius: 3px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
    </style>";
    
    // Function to get users with caching
    function getUsersWithCache($pdo, $redis, $forceDb = false) {
        $cacheKey = 'users:all';
        $cacheTime = 30; // Cache for 30 seconds
        
        if (!$forceDb && $redis->exists($cacheKey)) {
            // Cache hit
            $users = json_decode($redis->get($cacheKey), true);
            $source = '🔵 REDIS CACHE';
            $hit = true;
        } else {
            // Cache miss - get from database
            $stmt = $pdo->query("SELECT * FROM users");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Store in Redis
            $redis->setex($cacheKey, $cacheTime, json_encode($users));
            $source = '🟢 MYSQL DATABASE';
            $hit = false;
        }
        
        return ['users' => $users, 'source' => $source, 'hit' => $hit];
    }
    
    // Handle actions
    $action = $_GET['action'] ?? '';
    $message = '';
    
    if ($action === 'clear') {
        $redis->del('users:all');
        $message = "✅ Cache cleared!";
    } elseif ($action === 'add') {
        // Add a new user
        $newName = "User " . rand(100, 999);
        $newEmail = "user" . rand(100, 999) . "@example.com";
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
        $stmt->execute([$newName, $newEmail]);
        
        // Clear cache after insert
        $redis->del('users:all');
        $message = "✅ New user added: $newName - Cache cleared!";
    }
    
    // Get users (with cache)
    $forceDb = isset($_GET['force_db']);
    $result = getUsersWithCache($pdo, $redis, $forceDb);
    $users = $result['users'];
    $source = $result['source'];
    $isHit = $result['hit'];
    
    // Display cache status
    $cacheClass = $isHit ? 'cache-hit' : 'cache-miss';
    echo "<div class='$cacheClass'>";
    echo "<strong>📊 Data Source:</strong> $source<br>";
    echo "<strong>⚡ Cache " . ($isHit ? 'HIT' : 'MISS') . "</strong><br>";
    if ($message) echo "<strong>📝 Message:</strong> $message<br>";
    echo "</div>";
    
    // Action buttons
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='?action=add' class='btn btn-primary'>➕ Add Random User</a> ";
    echo "<a href='?action=clear' class='btn btn-danger'>🗑️ Clear Cache</a> ";
    echo "<a href='?force_db=1' class='btn btn-primary'>🔄 Force DB Query</a> ";
    echo "<a href='?' class='btn btn-primary'>📦 Use Cache</a>";
    echo "</div>";
    
    // Display users
    echo "<h2>Users in Database:</h2>";
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Cache stats
    echo "<div class='stats' style='margin-top: 20px;'>";
    echo "<h3>📈 Cache Statistics:</h3>";
    echo "<strong>Cache Key:</strong> users:all<br>";
    echo "<strong>TTL:</strong> " . ($redis->ttl('users:all') > 0 ? $redis->ttl('users:all') . ' seconds' : 'No cache') . "<br>";
    echo "<strong>Cache Size:</strong> " . round(strlen($redis->get('users:all') ?: '') / 1024, 2) . " KB<br>";
    
    // Redis info
    $info = $redis->info();
    echo "<strong>Redis Memory:</strong> " . round($info['used_memory'] / 1024 / 1024, 2) . " MB / " . round($info['maxmemory'] / 1024 / 1024, 2) . " MB<br>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "❌ Redis Error: " . $e->getMessage();
}

$end = microtime(true);
$executionTime = ($end - $start) * 1000;
echo "<hr><strong>Page generated in: " . round($executionTime, 2) . " ms</strong>";