# Africa Quizz - Guide de démarrage Frontend

Objectif : développer la PWA, le site public et le back-office.

## Stack technique

- PWA : React 18 + Vite + Tailwind CSS + TanStack Query + React Router
- Site public : Next.js 14 (App Router)
- Back-office : Next.js 14 (App Router)
- Types : TypeScript strict

## Étape 1 : Créer le projet PWA

npm create vite@latest africaquizz-pwa -- --template react-ts
cd africaquizz-pwa
npm install
npm install -D tailwindcss postcss autoprefixer
npm install @tanstack/react-query react-router-dom axios
npx tailwindcss init -p

## Étape 2 : Configurer .env

Copier .env.example vers .env :

VITE_API_URL=http://127.0.0.1:8000

## Étape 3 : Configurer Tailwind (couleurs Africa Quizz)

Dans tailwind.config.js, ajouter dans theme.extend :

colors: {
  brand: {
    red: "#FF2C19",
    gold: "#FFBB02",
    green: "#25BF6A",
  }
},
fontFamily: {
  title: ["Fredoka", "sans-serif"],
  body: ["Nunito", "sans-serif"],
},

## Étape 4 : Client API (axios)

Fichier src/lib/api.ts :

import axios from "axios";
export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { "Content-Type": "application/json" },
  withCredentials: true,
});
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

## Étape 5 : Structure recommandée

src/
  components/
    ui/           Boutons, cards, modales
    quiz/         Question, Timer, Score
    layout/       Navbar, Footer
  pages/
    Home.tsx
    Onboarding.tsx
    Quiz/
      Start.tsx
      Play.tsx
      Result.tsx
    DailyChallenge.tsx
    Leaderboard.tsx
    Profile.tsx
    Settings.tsx
  hooks/
    useAuth.ts
    useQuiz.ts
    useDailyChallenge.ts
  lib/
    api.ts
    auth.ts
  App.tsx

## Étape 6 : Écrans prioritaires

1. Splash (chargement)
2. Onboarding (choix pays ou catégorie + 5 questions essai)
3. Inscription / Connexion
4. Création de profil (pseudo, avatar, pays)
5. Accueil (défi du jour, modes, série)
6. Choix du mode (classic, rapid, daily)
7. Sélection catégorie + difficulté
8. Question (timer, options, image)
9. Feedback (correct, explication, source)
10. Résultats (score, XP, badges, partage)
11. Profil (stats, badges, série)
12. Classements (4 scopes)
13. Défi du jour
14. Explorer l Afrique
15. Révision (SRS)
16. Défis amis
17. Paramètres
18. Back-office (interface admin)

## Ordre de développement recommandé

1. Setup : Vite + Tailwind + Axios + Router (2h)
2. Auth : Login + Register + Persistence (4h)
3. Onboarding : 5 questions en invité (4h)
4. Quiz : Question + Timer + Answer + Feedback (8h)
5. Résultats : Score + XP + Partage (2h)
6. Accueil : Défi du jour + Modes + Série (4h)
7. Défi du jour : Start + Play + Résultat (4h)
8. Profil : Stats + Badges + Export RGPD (4h)
9. Classements : 4 scopes + 4 périodes (4h)
10. PWA : Manifest + Service Worker + Install (2h)

Total MVP PWA : environ 42h de dev

## Ressources disponibles

- Documentation API : africaquizz-contract/docs.html (ouvrir dans navigateur)
- Postman : importer africaquizz-contract/postman_collection.json
- Types TypeScript : africaquizz-contract/api-types.ts
- Exemples payloads : africaquizz-contract/examples/*.http
- Routes brutes : africaquizz-contract/routes.json

## Comptes d'administration locaux

Les seeders d'administration exigent des mots de passe définis dans le fichier `.env` local. N'ajoutez jamais ces mots de passe au dépôt.

## Backend URL

http://127.0.0.1:8000

## Checklist avant de commencer

- Node 20+ installé
- Backend lancé (`docker compose up -d`)
- `http://127.0.0.1:8000/up` répond HTTP 200
- .env configuré avec VITE_API_URL
- Repo frontend cloné
- Vite + Tailwind + Axios + Router installés

## Si l API ne répond pas

1. Vérifier que Docker tourne : docker-compose ps
2. Tester : curl http://localhost:8000/api/v1/push/vapid-key
3. Logs backend : docker-compose exec api tail -f storage/logs/laravel.log
4. Console navigateur : chercher les erreurs CORS

Bon dev !