# Africa Quizz

Africa Quizz est une application de quiz sur l’Afrique. Le dépôt contient l’API Laravel, la PWA React, les contrats API et les ressources de contenu.

## Installation locale sous Windows

Consultez le [guide PDF d’installation locale](docs/installation-locale.pdf) ou sa [version HTML imprimable](docs/installation-locale.html). Il décrit le démarrage de PostgreSQL et de l’API avec Docker Compose, le lancement de la PWA, les tests et les vérifications manuelles.

## Démarrage rapide

Prérequis : Git, Docker Desktop avec Compose v2 et Node.js LTS.

```powershell
Copy-Item africaquizz-api\.env.example africaquizz-api\.env
docker compose build api
docker compose run --rm api php artisan key:generate
docker compose up -d
docker compose exec api php artisan migrate --seed --force
```

Dans un autre terminal :

```powershell
Copy-Item africaquizz-pwa\.env.example africaquizz-pwa\.env
Set-Location africaquizz-pwa
npm ci
npm run dev -- --host 127.0.0.1
```

Ouvrez `http://127.0.0.1:5173/bienvenue`. Le bouton « Jouer 5 questions » démarre une partie invitée à partir des questions locales de démonstration.

## Tests

```powershell
# Depuis la racine
docker compose exec api php artisan test
Set-Location africaquizz-pwa
npm run build
```

Les données de démonstration sont créées uniquement dans les environnements `local` et `testing`. Les sauvegardes SQL et les fichiers `.env` locaux ne doivent pas être publiés ; le dépôt les exclut.
