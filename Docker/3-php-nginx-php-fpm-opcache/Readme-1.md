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