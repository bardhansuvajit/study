<?php
$redis = new Redis();
$redis->connect('redis', 6379);

// Set cache
$redis->set('test_key', 'Hello from Redis!', 3600);

// Get cache
echo $redis->get('test_key');

// Check if Redis is working
echo $redis->ping() ? '✅ Redis connected' : '❌ Redis failed';