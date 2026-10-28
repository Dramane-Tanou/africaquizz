# Africa Quizz API

API REST Laravel pour Africa Quizz.

## Démarrage local

Le guide complet pour Windows et les commandes Docker est dans [`docs/installation-locale.pdf`](../docs/installation-locale.pdf).

À la racine du dépôt :

```powershell
Copy-Item africaquizz-api\.env.example africaquizz-api\.env
docker compose build api
docker compose run --rm api php artisan key:generate
docker compose up -d
docker compose exec api php artisan migrate --seed --force
```

Le service API écoute ensuite sur `http://127.0.0.1:8000`. La base locale est initialisée avec cinq questions bilingues de démonstration ; elles ne sont ajoutées qu'en environnement `local` ou `testing`.

## Vérifications

```powershell
docker compose exec api php artisan test
docker compose exec api php artisan route:list
```

La configuration Compose lance le serveur HTTP Laravel, attend que PostgreSQL soit prêt et utilise les dépendances Composer installées dans l'image.

## Données locales

Les sauvegardes de base de données contenant des comptes ou des données personnelles ne sont pas incluses dans le dépôt. Utilisez uniquement une sauvegarde locale autorisée et ne la publiez pas.
