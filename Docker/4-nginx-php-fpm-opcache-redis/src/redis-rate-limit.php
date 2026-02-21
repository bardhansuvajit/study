<?php
// redis-rate-limit.php - Implement rate limiting with Redis
$start = microtime(true);

$redis = new Redis();
$redis->connect('redis', 6379);
$redis->select(3); // Use database 3 for rate limiting

// Get client IP (simplified for demo)
$clientId = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
if (isset($_GET['user'])) {
    $clientId = 'user_' . $_GET['user']; // For testing different users
}

// Rate limit configuration
$maxRequests = 10; // Max requests per window
$windowSeconds = 60; // 1 minute window

$key = "rate_limit:{$clientId}";
$current = $redis->get($key);

echo "<h1>⏱️ Rate Limiting with Redis</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .allowed { background: #d4edda; padding: 15px; border-radius: 5px; }
    .blocked { background: #f8d7da; padding: 15px; border-radius: 5px; }
    .stats { background: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px; }
    .progress { height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
    .progress-bar { height: 100%; background: #28a745; transition: width 0.3s; }
</style>";

if ($current !== false && $current >= $maxRequests) {
    // Rate limit exceeded
    $ttl = $redis->ttl($key);
    echo "<div class='blocked'>";
    echo "<h2>❌ Rate Limit Exceeded</h2>";
    echo "Client: <strong>$clientId</strong><br>";
    echo "You have made $current out of $maxRequests requests.<br>";
    echo "Please wait <strong>$ttl seconds</strong> before trying again.";
    echo "</div>";
} else {
    // Allow request and increment counter
    $requests = $redis->incr($key);
    if ($requests == 1) {
        $redis->expire($key, $windowSeconds);
    }
    
    $remaining = $maxRequests - $requests;
    $resetTime = $redis->ttl($key);
    
    echo "<div class='allowed'>";
    echo "<h2>✅ Request Allowed</h2>";
    echo "Client: <strong>$clientId</strong><br>";
    echo "Request #$requests in the last $windowSeconds seconds<br>";
    echo "Remaining requests: $remaining<br>";
    echo "Reset in: $resetTime seconds";
    echo "</div>";
}

// Show progress bar
$current = $redis->get($key) ?: 0;
$percentage = min(100, ($current / $maxRequests) * 100);
echo "<div class='stats'>";
echo "<h3>📊 Rate Limit Status</h3>";
echo "<div>Used: $current / $maxRequests requests</div>";
echo "<div class='progress'>";
echo "<div class='progress-bar' style='width: {$percentage}%'></div>";
echo "</div>";
echo "</div>";

// Test different users
echo "<div style='margin-top: 20px;'>";
echo "<h3>🔄 Test Different Users:</h3>";
echo "<a href='?user=alice'>Test as Alice</a> | ";
echo "<a href='?user=bob'>Test as Bob</a> | ";
echo "<a href='?user=charlie'>Test as Charlie</a> | ";
echo "<a href='?'>Reset to current user</a>";
echo "</div>";

// Show all rate limits
echo "<div class='stats'>";
echo "<h3>📋 Current Rate Limits in Redis:</h3>";
$keys = $redis->keys('rate_limit:*');
if (count($keys) > 0) {
    foreach ($keys as $key) {
        $count = $redis->get($key);
        $ttl = $redis->ttl($key);
        $user = str_replace('rate_limit:', '', $key);
        echo "<div><strong>$user:</strong> $count requests (expires in $ttl seconds)</div>";
    }
} else {
    echo "No active rate limits";
}
echo "</div>";

$end = microtime(true);
$executionTime = ($end - $start) * 1000;
echo "<hr><strong>Page generated in: " . round($executionTime, 2) . " ms</strong>";