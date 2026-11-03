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

## Tester en local avec toutes les questions

### 1. Démarrer l’API

Lancez Docker Desktop, puis depuis la racine :

```powershell
docker compose up -d
docker compose ps   # aq_api, aq_postgres et aq_redis doivent être « Up »
```

### 2. Importer la banque de questions

`database/data/questions.json` contient 1764 questions : 1709 publiées et 55 à vérifier, réparties sur 12 catégories. L’import est rejouable sans créer de doublons.

```powershell
docker compose exec api php artisan db:seed --class=CategorySeeder --force
docker compose exec api php artisan db:seed --class=CountrySeeder --force
docker compose exec api php artisan db:seed --class=QuestionBankSeeder --force
```

### 3. Créer les comptes d’administration

Renseignez les mots de passe de votre choix dans `africaquizz-api\.env` :

```dotenv
AFRICAQUIZZ_SUPERADMIN_PASSWORD=...
AFRICAQUIZZ_ADMIN_PASSWORD=...
AFRICAQUIZZ_REVIEWER_PASSWORD=...
AFRICAQUIZZ_EDITOR_PASSWORD=...
```

Créez ensuite les comptes `superadmin@`, `admin@`, `relecteur@` et `editeur@africaquizz.com` :

```powershell
docker compose restart api
docker compose exec api php artisan db:seed --class=GuideAccountsSeeder --force
```

Si ces comptes existent déjà dans votre base, ne relancez pas ce seeder : il remplacerait leurs mots de passe par ceux du `.env`. En local, `docker-compose.yml` active `AUTH_2FA_BYPASS` : la double authentification n’est pas demandée.

### 4. Lancer la PWA

```powershell
Set-Location africaquizz-pwa
npm ci
npm run dev -- --host
```

| Page | Adresse |
|------|---------|
| Jeu | http://localhost:5173 |
| Espace d’administration | http://localhost:5173/admin/connexion |
| API | http://127.0.0.1:8000 |

### 5. Tester sur un téléphone

Connectez le téléphone au même Wi-Fi que le PC et ouvrez l’adresse `Network` affichée par Vite, par exemple `http://192.168.1.20:5173`. La PWA appelle automatiquement l’API sur cette même IP, et l’API accepte les IP de réseau local en environnement `local`. Si la page ne se charge pas, autorisez Node.js et Docker dans le pare-feu Windows (réseau privé).

### Dépannage

- **« Serveur injoignable » ou « Connexion impossible. »** : vérifiez que `docker compose ps` montre `aq_api` démarré, puis ouvrez l’app via `http://localhost:5173`.
- **Une modification du code PHP n’est pas prise en compte** : l’OPcache du conteneur ne relit pas les fichiers modifiés (`opcache.validate_timestamps = 0`). Lancez `docker compose restart api`.
- **Une modification de `config/*.php` ou du `.env`** : lancez `docker compose exec api php artisan config:clear`, puis `docker compose restart api`.

## Tests

```powershell
# Depuis la racine
docker compose exec api php artisan test
Set-Location africaquizz-pwa
npm run build
```

Les données de démonstration sont créées uniquement dans les environnements `local` et `testing`. Les sauvegardes SQL et les fichiers `.env` locaux ne doivent pas être publiés ; le dépôt les exclut.
