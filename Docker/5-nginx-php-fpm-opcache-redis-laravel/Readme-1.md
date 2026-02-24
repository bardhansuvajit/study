project-root/
├── backend/                        # laravek app is here
│   ├── ...
├── docker/
│   ├── .env                        # Docker environment variables. Not Laravel .env
│   ├── php/
│   │   ├── Dockerfile              # PHP with Composer & Node
│   │   └── opcache.ini             # Your opcache config
│   ├── nginx/
│   │   └── default.conf            # Nginx config
│   └── mysql/
│       └── my.cnf                  # Optional MySQL config
├── docker-compose.yml              # Uses docker/.env
├── .env                            # Docker env variables. Not Laravel .env. keep it in same directory as compose
└── README.md



🔥 NEXT STEPS
1️⃣ after all setup, goto 'project-root' folder
2️⃣ Make sure "backend" folder is empty. else, rm -rf backend && mkdir backend && docker compose down -v
3️⃣ docker compose up -d --build
4️⃣ docker compose exec db mysql -ularavel -psecret -e "SHOW DATABASES;"
check available Databases. check if MYSQL_DATABASE from docker/.env is created
5️⃣ docker compose exec app composer create-project laravel/laravel .
Install Laravel in 'backend' folder  OR  cd backend && composer create-project laravel/laravel .
6️⃣ update laravel .env file
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel12_react
DB_USERNAME=laravel
DB_PASSWORD=secret
7️⃣ docker compose exec app php artisan migrate

🔥 After Installation
8️⃣ docker compose exec app bash
Enter PHP container

docker compose exec node npm install
docker compose exec node npm run dev
