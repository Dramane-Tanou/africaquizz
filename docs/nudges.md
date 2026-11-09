# Nudges et engagement dans Africa Quizz

Ce document décrit les incitations (« nudges ») de l'application et les notions de design comportemental sur lesquelles elles reposent. Règle commune, celle du **nudge éthique** : chaque incitation est **transparente**, laisse le **choix libre** (on peut l'ignorer ou la désactiver) et sert **l'intérêt du joueur** (apprendre régulièrement). Une incitation qui ne respecte pas ces trois critères devient un *dark pattern* et n'a pas sa place ici, d'autant que le public commence à 13 ans.

## Le modèle HOOK appliqué au quiz

| Étape | Dans Africa Quizz |
|---|---|
| Déclencheur | Externe : notification du défi du jour, défi reçu d'un ami sur WhatsApp. Interne : la curiosité (« est-ce que je connais mon pays ? »). |
| Action | Une partie de 10 questions, lancée en un geste depuis la carte « Ta prochaine étape ». |
| Récompense variable | Questions nouvelles à chaque partie, combo, bonus de vitesse, badge inattendu, place qui change dans le classement du pays. |
| Investissement | Série de jours, XP et niveau, collection « Pays explorés », file de révision personnalisée. |

## Les nudges, par famille

| Famille | Nudge | Où | Garde-fou |
|---|---|---|---|
| Faciliter (choix par défaut, positionnement) | **Ta prochaine étape** : une seule action proposée (défi du jour → révisions dues → objectif du jour). | Accueil | Quand tout est fait, la carte le dit et ne propose plus de jouer. |
| Faciliter | Partie d'essai sans compte, connexion Google en un geste. | Accueil invité | — |
| Comparer (norme sociale) | **« Seuls 23 % des joueurs ont trouvé »** après chaque réponse. | Écran de réponse | Calculé sur les vraies réponses, affiché seulement à partir de 20 réponses. |
| Comparer (statut) | Place dans le classement hebdomadaire du pays et écart avec le joueur devant. | Accueil | Classement par semaine : tout le monde repart à zéro. |
| Renforcer (feedback, objectif) | Série, objectif du jour (3 parties), XP restant avant le niveau suivant. | Accueil, résultats | L'objectif du jour a une fin claire. Gel de série offert tous les 7 jours. |
| Renforcer (investissement) | **Pays explorés** : collection qui grandit à chaque bonne réponse sur un nouveau pays. | Accueil | Progression liée à l'apprentissage, pas au temps passé. |
| Confronter (friction bienveillante) | **Proposition de pause** après 5 parties d'affilée ou 30 minutes, et plus tôt la nuit (22 h – 6 h). | Résultats | Désactivable dans les paramètres ; « Encore une partie » reste possible. |
| Transparence | « Pourquoi je vois ces rappels ? » explique chaque incitation. | Paramètres | — |

## Ce que l'application ne fait pas

- Pas de fausse rareté ni de faux compteur (« plus que 2 places ! »).
- Pas de leurre de prix ni d'option du milieu artificielle dans l'abonnement.
- Pas de culpabilisation (« tu vas tout perdre ») ni de notification la nuit pour les mineurs.
- Pas de jeu sans fin : pas de lecture automatique de partie en partie.
- Pas de récompense aléatoire payante (coffres, loot boxes).

## Indicateurs à suivre

Rétention J1 / J7 / J30, part des joueurs qui atteignent l'objectif du jour, part des pauses acceptées, temps de session médian. Une hausse du temps de session **sans** hausse des bonnes réponses ou de la rétention est un signal d'alerte : l'engagement ne doit pas devenir compulsif.
