✅1️⃣ Folder Structure

project/
│
├── Dockerfile
├── docker-compose.yml
├── opcache.ini
├── nginx/
│   └── default.conf
└── src/
    └── index.php


// to check opcache in action, simply hit http://localhost:8080/
// it will take around30ms to load for forst time, next it will load in 4-5ms



🔥 OPcache stores compiled PHP bytecode in memory so PHP doesn’t recompile files on every request.

🔥 Whats in opcache.ini
opcache.enable=1
# Turns OPcache on for web requests. Dev-1, Prod-1

opcache.enable_cli=1
# Enables OPcache for CLI scripts (php artisan, cron jobs, queues). Dev-0, Prod-0
# Laravel queue workers and schedulers benefit from this.
# Production: 0 (unless running long-lived CLI processes)

opcache.memory_consumption=128
# How much RAM OPcache can use (in MB). Dev-128, Prod-256, Large SASS app-512+
# If too low - Cache fills up, PHP recompiles files → slower

opcache.interned_strings_buffer=8
# Memory for storing duplicate strings once. Dev-8, Prod-16, Large app-64
# its about string values, not variable names
# Example, table names: "users", "migrations", column names: "email", "password", config keys: "database.connections.mysql", route paths: "/login"

opcache.max_accelerated_files=10000
# Max number of PHP files OPcache can store. Dev-10000, Prod-20000
# Laravel file counts - Fresh install: ~4,000, Medium app: 8,000–12,000, Large SaaS: 20,000+

opcache.validate_timestamps=0
# Means - Should PHP check if files changed? Dev-1, Prod-0
# disables file checking → maximum speed/ Good for benchmarking, not development

opcache.revalidate_freq=0
# How often (seconds) to check file changes. Works only if validate_timestamps=1.
# Examples - 0 → check every request, 2 → check every 2 seconds, 60 → check every minute
# Dev: 0 (instant updates), Production: irrelevant if validation off



🔥 How to Verify OPcache is working
RUN - phpinfo(); and Look for, Opcode Caching => Up and Running
IN Laravel, This works together with OPcache for huge performance gains.
php artisan config:cache
php artisan route:cache
php artisan view:cache



🔥 SO FAR
we have used,
✅ Nginx
✅ PHP-FPM
✅ OPcache
✅ MySQL
✅ Volume mapping
✅ Benchmark hooks (CPU + DB stress)
This is exactly how modern PHP apps run in production.

🔧 Recommended PHP Extensions
Right now these are installed
    docker-php-ext-install pdo pdo_mysql opcache
Must-have for Laravel
    docker-php-ext-install \
        pdo \
        pdo_mysql \
        opcache \
        bcmath \            # math precision (payments, calculations)
        intl \              # localization, formatting
        zip \               # file exports/imports
        exif \              # image metadata
        pcntl               # queues & workers

🔥 Redis is the next logical step
Without Redis
    DB handles sessions
    DB handles caching
    queues slow
With Redis
    lightning-fast sessions
    caching without DB load
    queue workers scale
    rate limiting possible

