✅1️⃣ Folder Structure

project/
    ├── Dockerfile
    ├── docker-compose.yml
    ├── nginx/
    │   └── default.conf
    └── src/
        └── index.php


✅2️⃣ NGINX + PHP-FPM app
// docker-compose.yml
services:
...

// nginx\default.conf
server {                                                                # Server Block. in case of multiple domains, create multiple server blocks
    listen 80;                                                          # Nginx listens on port 80 inside the container for this domain.
    server_name localhost;                                              # This block responds to requests for localhost

    root /var/www/html;                                                 # This is the folder Nginx looks in when someone requests a file.
    index index.php index.html;                                         # If someone requests /, Nginx will look for: index.php index.html

    location / {
        try_files $uri $uri/ /index.php?$query_string;                  # $uri → exact file match | $uri/ → directory match | /index.php?$query_string → fallback to PHP
    }                                                                   
    # when user requests /users?page=2 , 
    # Nginx checks: /var/www/html/users (file exists?) ❌ /var/www/html/users/ (folder exists?) ❌ Redirect to: /index.php?page=2 | thats how laravel works | makes PHP file behave like a router

    location ~ \.php$ {                                                 # PHP Handling Block, means for any file ending in .php
        fastcgi_pass app:9000;                                          # this tell Nginx, Send PHP execution to another service called 'app' on port 9000.
        fastcgi_index index.php;                                        # Default index for FastCGI.
        include fastcgi_params;                                         # Loads required CGI parameters. PHP breaks without this
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;           # full path of the file to execute.

        # full path of the file to execute. when root is /var/www/html, & user requests /index.php, it becomes /var/www/html/index.php. Without this line → PHP-FPM doesn’t know what file to run. 
    }
}

