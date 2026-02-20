🔥 Next Steps (Docker Focused)

1️⃣ Multi-Container Architecture
    Upgrade your setup:
        PHP-FPM container
        Nginx container
        MySQL container
        Redis container

2️⃣ Learn Docker Networking (Critical before Kubernetes)
    default bridge network
    custom networks
    container DNS resolution
    internal vs external ports

3️⃣ Learn Docker Volumes Properly
    Named volumes
    Bind mounts
    Anonymous volumes
    Break and recreate DB data to see behavior.

4️⃣ Learn Docker Images & Layers (This is real DevOps skill)
    Understand:
        How layers work
        Why build cache exists
        Multi-stage builds
        Reducing image size

5️⃣ Write Production-Ready Dockerfile (separates beginners from serious engineers)
    Practice:
        Use php:8.4-fpm
        Install only required extensions
        Use non-root user
        Optimize image size

6️⃣ Add Healthchecks (Very important before Kubernetes)
    Add healthcheck:
    Understand container readiness.

7️⃣ Use Environment Variables Properly
    Instead of hardcoding:
        MYSQL_PASSWORD: secret
    Use:
        .env files
    docker-compose env_file
    build args vs runtime env

8️⃣ Push Image to Docker Hub (Real DevOps workflow)
    Build image
    Tag image
    Push to registry
    Pull on another machine

9️⃣ Learn Docker CLI Deeply
    Practice:
        docker logs
        docker exec
        docker inspect
        docker network ls
        docker volume ls
        You should be comfortable debugging containers.

🚀 After That → Move to Kubernetes
    Only when you fully understand:
    Containers
    Networking
    Volumes
    Healthchecks
    Env management

🎯 Next plan:
    Convert app to Nginx + PHP-FPM
    Add Redis
    Add healthcheck
    Build optimized production Dockerfile
    Push image to registry
    Do this and you’re no longer “Docker beginner”.









Now, we are using a simple base PHP image - php:8.4-apache
This means,
Web server → Apache & PHP runs inside Apache (mod_php style)

In real production systems,
Web server → Nginx
PHP processor → PHP-FPM

🔥 What is Apache
Apache is a traditional web server. (A web server is a program that, listens on a port, usually 80 or 443, accepts HTTP requests & returns response in HTML/ JSON/ image etc.)
It:
    Accepts HTTP requests (browser → server)
    Serves static files (HTML, CSS, JS, images)
    Can execute PHP directly (via mod_php)
Characteristics:
    Process-based model (for every incoming request, apache creates new process and then serves/ handles one process at a time)
    Flexible & Easier for beginners
    Slightly heavier in modern setups (heavier cause each process consumes memory. more traffic = more processes = more RAM usage. thats why its less efficient under huge load)

🔥 What is Nginx
Nginx is also a web server. But:
    *️⃣ *️⃣ Event-driven/ non-blocking architecture (Few workers handle thousands of requests using events + when server waits for something like reading a file or DB response, it can be Blocking → Stop and wait OR Non-blocking → Continue handling other requests meanwhile. Nginx doesn’t sit idle waiting. If one request is waiting for I/O: It moves to another request & Comes back later when ready)
    Very fast with static files
    Lower memory usage
    Cannot execute PHP directly
Characteristics:
    Nginx does NOT run PHP by itself.
    It forwards PHP requests to PHP-FPM.

In modern production:
    Nginx handles traffic
    PHP-FPM executes PHP

🔥 What is PHP-FPM
PHP-FPM = PHP FastCGI Process Manager
It:
    Runs PHP code
    Manages a pool of PHP worker processes
    Handles multiple PHP executions efficiently
So:
    Nginx handles web traffic
    PHP-FPM handles PHP execution

🔥 How Does Nginx Forward PHP to PHP-FPM?
FastCGI - The protocol Nginx uses to talk to PHP-FPM.
When Nginx sees,
    example.com/index.php
It checks its config:
    location ~ \.php$ {
        fastcgi_pass php:9000;
    }
That means:
    This is a PHP file
    Send it to PHP-FPM on port 9000
    Wait for response
    Send result back to browser

Nginx handles:
    SSL (HTTPS)
    Static files
    Caching
    Load balancing
    Reverse proxy
    Rate limiting

PHP-FPM handles:
    Business logic
    Framework (Laravel)
    Database interaction

🔥 Why Modern Production Prefers Nginx + PHP-FPM?
    ✔ Lower memory usage
    ✔ Handles high traffic better
    ✔ Better performance under load
    ✔ Clean separation of responsibilities
    ✔ Scales horizontally better

    This is why most modern stacks use:
    Laravel + Nginx + PHP-FPM + MySQL

🔥 What is OPCache
OPcache is a PHP extension. PHP normally does these on every request
    Reads file
    Parses code
    Compiles to bytecode
    Executes
OPcache:
    Caches compiled bytecode in memory
    Skips recompilation
    Makes PHP MUCH faster
    In production, OPcache should always be enabled.













