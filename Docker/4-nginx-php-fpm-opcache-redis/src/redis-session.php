<?php
// redis-session.php - Store PHP sessions in Redis
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://redis:6379?database=2');

session_start();

$start = microtime(true);

echo "<h1>🔐 Session Management with Redis</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .session-data { background: #e3f2fd; padding: 15px; border-radius: 5px; }
    .form-group { margin: 10px 0; }
    input, select { padding: 8px; margin: 5px; }
    .btn { padding: 8px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
</style>";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_session'])) {
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['theme'] = $_POST['theme'];
        $_SESSION['last_visit'] = date('Y-m-d H:i:s');
        $_SESSION['visits'] = ($_SESSION['visits'] ?? 0) + 1;
    } elseif (isset($_POST['destroy'])) {
        session_destroy();
        session_start(); // Start new session after destroy
        echo "<div style='background: #dc3545; color: white; padding: 10px;'>Session destroyed!</div>";
    }
}

// Connect to Redis to show session data
try {
    $redis = new Redis();
    $redis->connect('redis', 6379);
    $redis->select(2); // Session database
    
    echo "<div class='session-data'>";
    echo "<h2>📊 Current Session</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Show all sessions in Redis
    echo "<h2>🗄️ All Sessions in Redis</h2>";
    $keys = $redis->keys('PHPREDIS_SESSION:*');
    if (count($keys) > 0) {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>Session ID</th><th>Data</th></tr>";
        foreach ($keys as $key) {
            $sessionId = str_replace('PHPREDIS_SESSION:', '', $key);
            $data = $redis->get($key);
            echo "<tr>";
            echo "<td>" . substr($sessionId, 0, 8) . "...</td>";
            echo "<td><small>" . htmlspecialchars($data) . "</small></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No active sessions";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "Redis error: " . $e->getMessage();
}
?>

<!-- Session Form -->
<div style="margin-top: 20px;">
    <h2>✏️ Update Session</h2>
    <form method="POST">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" name="username" value="<?php echo $_SESSION['username'] ?? ''; ?>" required>
        </div>
        <div class="form-group">
            <label>Theme:</label>
            <select name="theme">
                <option value="light" <?php echo ($_SESSION['theme'] ?? '') == 'light' ? 'selected' : ''; ?>>Light</option>
                <option value="dark" <?php echo ($_SESSION['theme'] ?? '') == 'dark' ? 'selected' : ''; ?>>Dark</option>
                <option value="auto" <?php echo ($_SESSION['theme'] ?? '') == 'auto' ? 'selected' : ''; ?>>Auto</option>
            </select>
        </div>
        <button type="submit" name="set_session" class="btn">Save to Session</button>
        <button type="submit" name="destroy" class="btn" style="background: #dc3545;">Destroy Session</button>
    </form>
</div>

<?php
$end = microtime(true);
$executionTime = ($end - $start) * 1000;
echo "<hr><strong>Page generated in: " . round($executionTime, 2) . " ms</strong>";
?>