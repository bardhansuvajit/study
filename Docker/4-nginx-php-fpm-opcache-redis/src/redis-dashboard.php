<?php
// redis-dashboard.php - Monitor Redis performance
$start = microtime(true);

try {
    $redis = new Redis();
    $redis->connect('redis', 6379);
    
    echo "<h1>📊 Redis Monitoring Dashboard</h1>";
    echo "<style>
        body { font-family: Arial; padding: 20px; background: #f8f9fa; }
        .card { background: white; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .metric { display: inline-block; width: 200px; margin: 10px; padding: 15px; background: #e9ecef; border-radius: 5px; }
        .metric-value { font-size: 24px; font-weight: bold; color: #007bff; }
        .metric-label { font-size: 12px; color: #6c757d; }
        .warning { background: #fff3cd; color: #856404; }
        .success { background: #d4edda; color: #155724; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .btn-danger { background: #dc3545; }
    </style>";
    
    // Handle actions
    if (isset($_GET['flush'])) {
        if ($_GET['flush'] === 'all') {
            $redis->flushAll();
            echo "<div class='success'>✅ All Redis databases flushed!</div>";
        } elseif ($_GET['flush'] === 'db') {
            $redis->flushDB();
            echo "<div class='success'>✅ Current database flushed!</div>";
        }
    }
    
    // Get Redis info
    $info = $redis->info();
    $dbSize = $redis->dbSize();
    
    // Server Info Card
    echo "<div class='card'>";
    echo "<h2>🖥️ Server Information</h2>";
    echo "<div class='metric'><div class='metric-value'>{$info['redis_version']}</div><div class='metric-label'>Redis Version</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$info['uptime_in_days']} days</div><div class='metric-label'>Uptime</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$info['connected_clients']}</div><div class='metric-label'>Connected Clients</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$dbSize}</div><div class='metric-label'>Total Keys</div></div>";
    echo "</div>";
    
    // Memory Info Card
    $usedMemory = round($info['used_memory'] / 1024 / 1024, 2);
    $maxMemory = isset($info['maxmemory']) ? round($info['maxmemory'] / 1024 / 1024, 2) : 'No limit';
    $memoryPeak = round($info['used_memory_peak'] / 1024 / 1024, 2);
    $memoryFrag = round($info['mem_fragmentation_ratio'] ?? 1, 2);
    
    echo "<div class='card'>";
    echo "<h2>💾 Memory Usage</h2>";
    echo "<div class='metric'><div class='metric-value'>{$usedMemory} MB</div><div class='metric-label'>Used Memory</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$maxMemory} MB</div><div class='metric-label'>Max Memory</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$memoryPeak} MB</div><div class='metric-label'>Peak Memory</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$memoryFrag}</div><div class='metric-label'>Fragmentation Ratio</div></div>";
    
    // Memory warning
    if ($maxMemory !== 'No limit' && ($usedMemory / $maxMemory) > 0.8) {
        echo "<div class='warning' style='padding: 10px;'>⚠️ Warning: Memory usage is above 80%!</div>";
    }
    echo "</div>";
    
    // Stats Card
    echo "<div class='card'>";
    echo "<h2>📈 Performance Statistics</h2>";
    echo "<div class='metric'><div class='metric-value'>{$info['total_commands_processed']}</div><div class='metric-label'>Total Commands</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$info['expired_keys']}</div><div class='metric-label'>Expired Keys</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$info['evicted_keys']}</div><div class='metric-label'>Evicted Keys</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$info['keyspace_hits']}</div><div class='metric-label'>Cache Hits</div></div>";
    echo "<div class='metric'><div class='metric-value'>{$info['keyspace_misses']}</div><div class='metric-label'>Cache Misses</div></div>";
    
    // Hit ratio
    $totalOps = $info['keyspace_hits'] + $info['keyspace_misses'];
    $hitRatio = $totalOps > 0 ? round(($info['keyspace_hits'] / $totalOps) * 100, 2) : 0;
    echo "<div class='metric'><div class='metric-value'>{$hitRatio}%</div><div class='metric-label'>Hit Ratio</div></div>";
    echo "</div>";
    
    // Database Keys Card
    echo "<div class='card'>";
    echo "<h2>🔑 Database Keys</h2>";
    
    // Get keys from each database
    for ($db = 0; $db <= 15; $db++) {
        $redis->select($db);
        $keys = $redis->keys('*');
        if (count($keys) > 0) {
            echo "<h3>Database $db (" . count($keys) . " keys)</h3>";
            echo "<table>";
            echo "<tr><th>Key</th><th>Type</th><th>TTL</th><th>Size</th></tr>";
            
            // Show first 10 keys
            $count = 0;
            foreach ($keys as $key) {
                if ($count++ >= 10) {
                    echo "<tr><td colspan='4'>... and " . (count($keys) - 10) . " more keys</td></tr>";
                    break;
                }
                
                $type = $redis->type($key);
                $ttl = $redis->ttl($key);
                $ttlDisplay = $ttl > 0 ? $ttl . 's' : ($ttl == -1 ? 'No expiry' : 'Expired');
                
                // Get size based on type
                if ($type == Redis::REDIS_STRING) {
                    $size = strlen($redis->get($key));
                } else {
                    $size = 'N/A';
                }
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($key) . "</td>";
                echo "<td>" . [
                    Redis::REDIS_STRING => 'String',
                    Redis::REDIS_SET => 'Set',
                    Redis::REDIS_LIST => 'List',
                    Redis::REDIS_HASH => 'Hash',
                    Redis::REDIS_ZSET => 'Sorted Set'
                ][$type] . "</td>";
                echo "<td>$ttlDisplay</td>";
                echo "<td>" . ($size != 'N/A' ? round($size / 1024, 2) . ' KB' : 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "</div>";
    
    // Actions Card
    echo "<div class='card'>";
    echo "<h2>🛠️ Actions</h2>";
    echo "<a href='?flush=db' class='btn btn-danger' onclick='return confirm(\"Flush current database?\")'>🗑️ Flush Current DB</a> ";
    echo "<a href='?flush=all' class='btn btn-danger' onclick='return confirm(\"Flush ALL databases? This cannot be undone!\")'>⚠️ Flush All DBs</a> ";
    echo "<a href='?' class='btn'>🔄 Refresh</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "❌ Redis Error: " . $e->getMessage();
}

$end = microtime(true);
$executionTime = ($end - $start) * 1000;
echo "<hr><strong>Page generated in: " . round($executionTime, 2) . " ms</strong>";