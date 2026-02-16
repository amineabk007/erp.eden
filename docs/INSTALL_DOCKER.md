# Installation (Docker) — Dev

## Prérequis
- Docker + Docker Compose

## Lancer
- `docker compose up -d --build`

## Entrer dans le container PHP
- `docker compose exec app bash`

## Installer dépendances
- `composer install`
- `cp .env.example .env`
- configurer DB (host=db, port=3306, user=erp, pass=erp)
- `php artisan key:generate`
- `php artisan migrate --seed`

## Accès
- http://localhost:8080
