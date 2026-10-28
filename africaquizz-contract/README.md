# Africa Quizz API v1.1.0

Documentation et contrats API pour l'application Africa Quizz.

## Base URL

http://127.0.0.1:8000

## Authentification

1. POST /api/v1/auth/login ou /api/v1/admin/auth/login
2. Recuperer le token dans la reponse
3. En-tête : `Authorization: Bearer <token>`

Les comptes de test du guide peuvent être créés dans la base locale avec `GuideAccountsSeeder`. Leurs mots de passe sont lus depuis les variables `AFRICAQUIZZ_SUPERADMIN_PASSWORD`, `AFRICAQUIZZ_ADMIN_PASSWORD`, `AFRICAQUIZZ_REVIEWER_PASSWORD` et `AFRICAQUIZZ_EDITOR_PASSWORD` du fichier `.env` local.

## Modules disponibles

- Auth utilisateur (register, login, guest, logout, verify-email, forgot-password)
- Quiz (sessions, answers, finish)
- Defi du jour (daily-challenge)
- Classements (leaderboards)
- Pays du profil (GET countries, PATCH me/profile)
- Progression (me/progress, me/export, me)
- Revision (review/queue, review/sessions)
- Defis amis (challenges)
- Duels (duels)
- Ligues (leagues)
- Push notifications (push/vapid-key, push/subscribe)
- Admin (auth, dashboard, gestion des joueurs, modération, audit)
- Relecteur (file de revue, checklist de validation, signalements)
- Éditeur (workflow de publication, imports bilingues, médiathèque)
- SuperAdmin (pilotage global, utilisateurs, comptes admin, badges, catégories, pays, régions et audit)

Les permissions de ces opérations sont contrôlées côté API en fonction du rôle, en complément des espaces dédiés `/admin/superadmin`, `/admin/admin`, `/admin/relecteur` et `/admin/editeur`.

## Fichiers de documentation

- routes.json : Toutes les routes Laravel en JSON
- postman_collection.json : Collection Postman importable
- docs.html : Documentation HTML a ouvrir dans le navigateur
- openapi.json : (si disponible) specification OpenAPI

## Etat de la base

- 19 questions (dont 8 importees par IA)
- Le catalogue local comprend 250 pays et territoires ISO ; exécuter `CountrySeeder` après les migrations.
