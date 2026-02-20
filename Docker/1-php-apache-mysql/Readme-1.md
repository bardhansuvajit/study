A basic containerized PHP app using Docker
Master these - volumes, networking, env variables and you’re already ahead of many “Docker users”

1️⃣ Folder structure
php-1/
 ├─ docker-compose.yml
 ├─ Dockerfile
 └─ src/
     └─ index.php

docker-compose.yml
    👉 Defines services/ containers
    👉 What containers should run and how they talk to each other
    👉 Containers like PHP, MySQL, Nginx, Redis etc. can be added
Dockerfile
    👉 Defines how to build your image
    👉 How to prepare the environment inside the container.
    👉 Example: Install PHP, Add extensions, Set working directory, Copy files
src/
    👉 Your actual app code
    Docker does NOT care about this folder name. just update the compose file

2️⃣ What if folder structure changes?
    👉 If Dockerfile is in /docker: 
        build:
            context: .
            dockerfile: docker/Dockerfile

3️⃣ How to change PHP version
    In Dockerfile:
        FROM
            FROM php:8.2-apache
        TO
            php:8.3-apache
            php:8.1-apache
            php:7.4-apache
    Then rebuild:
        docker compose up -d --build

4️⃣ What you actually learned
    ✅ Containers isolate environments
    ✅ Images define environment setup
    ✅ Volumes sync local code into containers
    ✅ Ports expose container to browser
    ✅ Compose manages multi-container apps

5️⃣ Common Commands
    Run Containers      > docker compose up -d
    Rebuild             > docker compose up -d --build
    Stop                > docker compose down
    See running         > docker ps
    Stop Containers     > docker compose down
    Delete volume       > docker compose down -v

6️⃣ EXAMPLE
6️⃣.1️⃣ SIMPLE PHP APP
docker-compose.yml -                                # Defines services/ containers
services:                                           # a container definition/ it tells Docker - I want to run this container with these settings
    app:                                            # service name. name it anything, app, php, backend, web etc./ This is my PHP application container.
        build: .                                    # Build an image using Dockerfile in the current folder
        ports:                                      # connect local machine port to container port./ host_port:container_port
            - "8080:80"                             # Apache runs on port 80, when you visit http://localhost:8080, It forwards to port 80 inside container
        volumes:                                    # live connection between local folder and container folder./ local_path:container_path
            - ./src:/var/www/html                   # local 'src' folder. has to be present.

Dockerfile -                                        # Defines how to build your image
    FROM php:8.4-apache                             # base image PHP/ start from official PHP image with Apache. configured to run on port 80

    WORKDIR /var/www/html                           # working directory inside the container. Apache default document root is /var/www/html
    COPY ./src /var/www/html                        # copies files into the image during build time/ Copy local 'src' folder to container '/var/www/html'

🔥🔥WHATS EXACTLY HAPPENING HERE. After official PHP image is downloaded from Docker (FROM php:8.4-apache), 
    - Inside that image already exists:
        - Linux OS (Debian-based)
        - Apache installed
        - PHP installed
        - Apache is default configured to serve files from: /var/www/html (Apache’s default document root)
        - So before you add anything, the container already has:
            /
            ├─ bin
            ├─ etc
            ├─ var
            │   └─ www
            │       └─ html   ← Apache serves from here
    - WORKDIR /var/www/html
        - it tells Docker, From now on, any commands run inside this folder
        - it's like > cd /var/www/html
        - If not set WORKDIR, Docker works from root /, which is messy.
    - COPY ./src /var/www/html
        - happens at build time
        - in this line, 
            - it takes local src folder/ all codes
            - copies into the image
            - saves that as a new image layer
            - Now your image contains your code baked inside it.
    - when run,     docker compose build,   COPY happens here.                      (BUILD TIME)
    - when run,     docker compose up,      Container starts from built image.      (RUN TIME)
    - volumes:
        - ./src:/var/www/html
        - This overrides what COPY did.
        - COPY puts files into image. Volume replaces that folder when container runs
        - COPY is often unnecessary
        - Volume is what actually shows your live code
        - So Why Even Use COPY?
            - In production, You don’t mount local files.
            - once the image built with COPY ./src /var/www/html
            - then deploy the image to server. no volume needed
        - 🔥so main difference between volume & copy is, 
            - Copy happens at build time, when image is created. so Files become part of the image. You must rebuild to see changes:
            - Volume happens at runtime, when container starts. Files are linked live from your computer. No rebuild needed
            - in Copy, local file changed, container does NOT update, browser does not update
            - in Volume, local file changed, browser updates instantly
            - Copy is Used in production
            - Volume is Used in development
            - COPY = Take a photo of your folder and store it inside the image.
            - Volume = Mirror your folder live into the container.

    🧠 Mental Model
        - Base image gives > Linux + Apache + PHP
        - Then Dockerfile:
            - Sets working directory
            - Installs extensions (if any)
            - Copies app code
        - The Docker create final Image like > Linux + Apache + PHP + Our App
        - Then container runs that
        - When Container starts, docker compose up
            - Creates container from image
            - Mounts volumes (if any)
            - Maps ports (8080 → 80)
            - Starts Apache inside container
            - Apache serves files from /var/www/html
            - Browser hits: localhost:8080
    💥 Important
        - Dockerfile builds the environment.
        - docker-compose runs the environment.
        - Volumes override files at runtime.
        - Ports expose container to your machine.
* You already mounted ./src:/var/www/html in docker-compose. So this COPY is actually unnecessary in development.
* In production (no volumes), COPY is useful. In development, volume is better.


> docker compose up -d --build
> http://localhost:8080/
> php code should work

6️⃣.2️⃣ SIMPLE PHP + MYSQL APP
docker-compose.yml -
services:
  app:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
    depends_on:
      - db                                  # start database container before app. Without it PHP container might start first, Try connecting to DB and fail because DB container not started yet. With it: DB container starts first, Then PHP container starts
   db:                                      # MySQL database container
    image: mysql:8.0                        # use official MySQL 8 image
    restart: always                         # auto-restart if container Crashes/ stops unexpectedly/ Docker restarts. Docker will restart this image. WHY ? - Databases are critical. If MySQL crashes, the app breaks. restart: always makes it more stable.
    environment:                            # environment variables passed to MySQL
      MYSQL_ROOT_PASSWORD: root             # root password. MySQL image refuses to start without it. Without it, anyone could connect as root with no password.
      MYSQL_DATABASE: testdb                # auto-create this database
      MYSQL_USER: testuser                  # create custom user. Because root user is only for administration. If app gets hacked: Attacker can only access testdb. Not destroy entire server
      MYSQL_PASSWORD: secret                # password for custom user
    ports:
      - "3307:3306"                         # localhost:3307 → container MySQL port 3306/ host_port:container_port/ Your computer port 3307 → MySQL container port 3306. Why not 3306:3306? - Your local machine might already have MySQL running. Port 3306 could be occupied
    volumes:
      - dbdata:/var/lib/mysql               # store database data persistently. Inside MySQL container, this folder stores: DB files, Tables, Data, Indexes. If volume not used, Container deleted - Database deleted - Data lost

volumes:
  dbdata:                                   # named volume for MySQL data storage. This tells Docker, Create a persistent storage area called dbdata. stores it outside the container filesystem. So even if container is deleted: Database remains.

Dockerfile -
    FROM php:8.4-apache

    # Install MySQL extension for PHP
    RUN docker-php-ext-install pdo pdo_mysql

    WORKDIR /var/www/html
    COPY ./src /var/www/html

index.php
    <?php
    $host = "db";
    $dbname = "testdb";
    $user = "testuser";
    $pass = "secret";

    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname",
            $user,
            $pass
        );

        echo "✅ Connected to MySQL successfully!";

        // create a table and insert some data
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL
        )");

        $pdo->exec("INSERT INTO users (name, email) VALUES 
            ('Alice', 'alice@example.com'),
            ('Bob', 'bob@example.com'),
            ('Charlie', 'charlie@example.com')");

        // show the data in table
        $stmt = $pdo->query("SELECT * FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h2>Users in Database:</h2>";
        foreach ($users as $user) {
            echo "<p>{$user['name']} - {$user['email']}</p>";
        }

    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage();
    }

> docker compose up -d --build
> http://localhost:8080/
> php + mysql code should work
> do "docker compose down"      & then build again,     the database & tables should stay
> do "docker compose down -v"   & then build again,     the database & tables will rebuild itself
> Connect DB Tool (Like DBeaver/ MySQL Workbench)
    > Host - localhost | user - testuser | passw - secret | port - 3307








