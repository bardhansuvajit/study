<?php
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    $configuration = opcache_get_configuration();
    
    echo "<h2>OPcache Status</h2>";
    echo "<pre>";
    echo "Memory usage: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB / " . 
         round($configuration['directives']['opcache.memory_consumption']) . " MB\n";
    echo "Cached files: " . $status['opcache_statistics']['num_cached_scripts'] . " / " . 
         $configuration['directives']['opcache.max_accelerated_files'] . "\n";
    echo "Hits: " . $status['opcache_statistics']['hits'] . "\n";
    echo "Misses: " . $status['opcache_statistics']['misses'] . "\n";
    echo "Cache full: " . ($status['opcache_statistics']['oom_restarts'] > 0 ? 'Yes' : 'No') . "\n";
    echo "</pre>";
    
    // Clear cache button (for development)
    if (isset($_GET['clear'])) {
        opcache_reset();
        echo "✅ OPcache cleared!";
    }
    
    echo '<br><a href="?clear=1">Clear OPcache</a>';
} else {
    echo "OPcache not enabled";
}