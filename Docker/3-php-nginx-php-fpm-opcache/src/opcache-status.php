<?php
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    echo '<pre>';
    print_r($status);
    echo '</pre>';
    
    echo "Memory usage: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB / " . 
         round($status['memory_usage']['free_memory'] / 1024 / 1024, 2) . " MB free<br>";
    echo "Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'];
} else {
    echo "OPcache not enabled";
}