<?php
// redis-basic.php - Basic Redis operations
$start = microtime(true);

try {
    $redis = new Redis();
    $redis->connect('redis', 6379);
    $redis->select(0); // Use database 0
    
    echo "<h1>🔴 Redis Basic Operations</h1>";
    echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .info { background: #f0f0f0; padding: 10px; }</style>";
    
    // Test connection
    echo "<h2>📡 Connection Test:</h2>";
    echo $redis->ping() ? "<span class='success'>✅ Redis connected successfully!</span>" : "❌ Redis connection failed";
    
    // String operations
    echo "<h2>📝 String Operations:</h2>";
    $redis->set('user:1:name', 'John Doe');
    $redis->setex('session:123', 10, 'active'); // Expires in 10 seconds
    
    echo "user:1:name = " . $redis->get('user:1:name') . "<br>";
    echo "session:123 = " . $redis->get('session:123') . " (expires in 10s)<br>";
    
    // List operations
    echo "<h2>📋 List Operations (Queue):</h2>";
    $redis->del('tasks'); // Clear existing
    $redis->rpush('tasks', 'task1', 'task2', 'task3'); // Add to end
    $redis->lpush('tasks', 'priority_task'); // Add to beginning
    
    $tasks = $redis->lrange('tasks', 0, -1); // Get all
    echo "Tasks in queue: " . implode(', ', $tasks) . "<br>";
    
    // Process one task
    $nextTask = $redis->lpop('tasks');
    echo "Processing: $nextTask<br>";
    echo "Remaining tasks: " . implode(', ', $redis->lrange('tasks', 0, -1)) . "<br>";
    
    // Set operations (unique values)
    echo "<h2>🎯 Set Operations (Unique):</h2>";
    $redis->sadd('tags:php', 'laravel', 'symfony', 'wordpress');
    $redis->sadd('tags:js', 'react', 'vue', 'angular');
    $redis->sadd('tags:php', 'laravel'); // Duplicate, won't be added
    
    $phpTags = $redis->smembers('tags:php');
    echo "PHP tags: " . implode(', ', $phpTags) . "<br>";
    
    // Hash operations (like objects)
    echo "<h2>🔑 Hash Operations (Objects):</h2>";
    $redis->hset('user:100', 'name', 'Jane Smith');
    $redis->hset('user:100', 'email', 'jane@example.com');
    $redis->hset('user:100', 'age', 28);
    
    $user = $redis->hgetall('user:100');
    echo "User data:<br>";
    foreach ($user as $key => $value) {
        echo "&nbsp;&nbsp;$key: $value<br>";
    }
    
    // Check if key exists
    echo "<h2>🔍 Key Existence:</h2>";
    echo "user:100 exists: " . ($redis->exists('user:100') ? '✅ Yes' : '❌ No') . "<br>";
    echo "non:existent exists: " . ($redis->exists('non:existent') ? '✅ Yes' : '❌ No') . "<br>";
    
    // Set expiration
    echo "<h2>⏰ Expiration:</h2>";
    $redis->setex('temp:key', 5, 'I will expire soon');
    echo "temp:key = " . $redis->get('temp:key') . " (TTL: " . $redis->ttl('temp:key') . " seconds left)<br>";
    
    // Wait 6 seconds (simulate)
    echo "Waiting 6 seconds...<br>";
    sleep(6);
    echo "temp:key now = " . ($redis->get('temp:key') ?: '❌ Expired') . "<br>";
    
    // Stats
    echo "<h2>📊 Redis Stats:</h2>";
    $info = $redis->info();
    echo "Redis version: " . $info['redis_version'] . "<br>";
    echo "Connected clients: " . $info['connected_clients'] . "<br>";
    echo "Memory used: " . round($info['used_memory'] / 1024 / 1024, 2) . " MB<br>";
    echo "Total keys: " . $info['db0']['keys'] ?? 0 . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

$end = microtime(true);
$executionTime = ($end - $start) * 1000;
echo "<hr><strong>Page generated in: " . round($executionTime, 2) . " ms</strong>";