# Africa Quizz — PWA (frontend joueur)

React 18 + TypeScript strict + Vite 6 + Tailwind CSS 3 + TanStack Query 5 + React Router 6 + react-i18next (FR/EN) + vite-plugin-pwa.

## Démarrer

```powershell
cd africaquizz-pwa
npm install
npm run dev        # http://localhost:5173
```

L'API Laravel doit tourner sur `http://127.0.0.1:8000` (voir `.env`). Sous Windows, garde `127.0.0.1` et non `localhost` (problème IPv6).

Autres commandes : `npm run typecheck`, `npm run build` (vérifie les types puis construit `dist/`), `npm run preview`.

## Écrans

| Route | Écran | Endpoints utilisés |
|---|---|---|
| `/bienvenue` | Onboarding : 5 questions d'essai en invité | `POST /quiz/sessions`, `POST …/answers` |
| `/inscription`, `/connexion` | Compte (âge minimum 13 ans, e-mail parental 13–15 ans) | `POST /auth/register`, `/auth/login` |
| `/admin/connexion` | Connexion des comptes SuperAdmin, Admin, Relecteur et Éditeur ; redirection vers l’espace du rôle après authentification | `/admin/auth/login`, `/admin/auth/2fa/*` |
| `/admin/superadmin` | Pilotage global, utilisateurs, comptes de l’équipe, taxonomie, contenu et audit | `/admin/super/*`, `/admin/audit-logs` |
| `/admin/admin` | Indicateurs, gestion des joueurs, modération, revue du contenu et audit | `/admin/super/dashboard/*`, `/admin/super/users`, `/admin/reports`, `/admin/audit-logs` |
| `/admin/relecteur` | Relecture bilingue, sources, checklist de validation et signalements | `/admin/questions`, `/admin/reports` |
| `/admin/editeur` | Workflow éditorial, imports CSV/JSON/XLSX et médiathèque | `/admin/questions`, `/admin/imports`, `/admin/media` |
| `/mot-de-passe-oublie`, `/reinitialiser`, `/verifier-email` | Récupération de compte | `/auth/forgot-password`, `/auth/reset-password`, `/auth/verify-email` |
| `/` | Accueil : niveau, série, défi du jour, modes | `/me/progress`, `/me/export`, `GET /daily-challenge` |
| `/jouer/:id` | Partie : minuteur, options, feedback, explication, combo | `…/answers`, `…/finish` |
| `/resultats/:id` | Score, XP, bonus, série, badges, partage | — (données du `finish`) |
| `/defi-du-jour` | Règles, statut, résultat du jour | `GET /daily-challenge`, `POST /daily-challenge/start` |
| `/classements` | 4 portées × 4 périodes, ma position | `GET /leaderboards` |
| `/revision` | File de répétition espacée | `/review/queue`, `/review/sessions` |
| `/profil` | Rang, niveau, série, stats, badges, avatar et choix du pays | `/me/progress`, `/me/profile`, `/countries` |
| `/parametres` | Langue, thème, minuteur visuel, export RGPD, suppression | `/me/export`, `DELETE /me` |

## Règles de jeu côté client

- Le serveur reste l'unique autorité sur les scores : le client n'affiche que ce que Laravel renvoie.
- Chaque réponse et chaque `finish` envoient un `Idempotency-Key` unique, réutilisé en cas de nouvel essai réseau.
- `client_elapsed_ms` est mesuré avec `performance.now()` ; `rtt_ms` = dernier aller-retour mesuré.
- Temps écoulé : la réponse `TIMEOUT` est envoyée (comptée fausse par le serveur).
- Mode rapide : 60 s au total, fin après 3 erreurs, enchaînement automatique.
- Une partie en cours est gardée dans `sessionStorage` : un rafraîchissement reprend où on en était.
- Raccourcis clavier en partie : `1`–`4` ou `A`–`D` pour répondre, `Entrée` pour continuer.

Les permissions de chaque espace sont également vérifiées par l’API ; masquer une action dans l’interface ne constitue pas le contrôle d’accès. En local, le bypass 2FA est activé uniquement par la configuration Docker de développement ; les environnements hors `local` et `testing` conservent le parcours 2FA.

## Structure

```
src/
  components/ui/       Boutons, cartes, champs, logo, avatars
  components/quiz/     Minuteur, options, panneau de feedback
  components/layout/   Coque (barre du haut, navigation), bandeaux
  context/             Auth (jeton Sanctum) et paramètres (langue, thème…)
  hooks/               Démarrage de partie, stats joueur, erreurs, réseau
  i18n/                Traductions fr / en (aucune chaîne en dur)
  lib/                 Client axios, endpoints typés, règles d'affichage, stockage
  pages/               Écrans
  types/api.ts         Copie de africaquizz-contract/api-types.ts
  types/extra.ts       Types complémentaires déduits des contrôleurs
```

## Limites connues liées au backend (à voir avec le développeur backend)

1. **Bonus de rapidité presque toujours à 0** : `QuizController::answer` calcule `t_eff = max(client, T − tolérance)` avec T le temps limite, au lieu du temps observé par le serveur. Le bonus ne dépasse donc jamais ~5 points.
2. **Pas de filtre catégorie / pays / difficulté** : `POST /quiz/sessions` n'accepte que `mode` et `locale`. L'écran de sélection n'est pas construit tant que l'API ne le permet pas.
3. **Profil** : le choix du pays est enregistré dans `country_id`. Le pseudo et l'avatar ne sont pas encore modifiables côté serveur.
4. **Badges** : `/me/export` renvoie les `UserBadge` sans le code du badge. Ajouter `$user->badges()->with('badge')->get()` dans `exportData` permet au profil de les afficher tous (le code le lit déjà s'il est présent).
5. **Inscription 13–15 ans** : la règle `required_if_age_between_13_and_15` n'est pas définie dans Laravel, elle provoque une erreur 500 si un e-mail parental est envoyé.
6. **Révision** : rien n'alimente `revision_queues`, la file reste vide.
7. **Messages d'erreur mal encodés** dans `QuizController` (« terminÃƒ… ») : le frontend les remplace par ses propres messages.
8. **Défis entre amis** : `GET /challenges/{code}` ne renvoie pas les questions, l'écran n'est donc pas encore faisable.
9. **Notifications push** : demandent un service worker personnalisé (`injectManifest`) ; prévu pour une étape suivante.
