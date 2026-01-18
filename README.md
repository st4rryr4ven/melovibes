# 🎵 Melovibes - Plateforme de Critiques Musicales

## 🌍 Déploiement & Dépôts
* **URL Frontend :** [INSÉRER LIEN ICI]
* **URL Backend (API) :** [INSÉRER LIEN ICI]
* **Dépôt Code Source :** https://gitlabinfo.iutmontp.univ-montp2.fr/bussierea/sites-de-critiques

---

## 📖 Présentation du Thème
Melovibes est une plateforme permettant de consulter et de critiquer des œuvres musicales.
* **Objets critiqués :** Titres musicaux.
* **Informations présentées :** Titre, artiste, genre, pochette, popularité et lien d'écoute.
* **Critères de critique :** Les utilisateurs attribuent des notes spécifiques sur la **Mélodie**, les **Paroles**, les **Vocaux** et l'**Impact** et une commentaire.

---

## 👥 L'Équipe & Répartition des Tâches
L'investissement a été réparti équitablement (33.3% par membre) pour couvrir l'ensemble du cycle de développement.

### 🛡️ Daniele 
* **Architecture & Initialisation :** Initialisation du projet Symfony/API Platform.
* **Sécurité (Voters) :** Implémentation de la logique complexe des droits (UserVoter, ReviewVoter, MusicVoter).
* **Gestion Utilisateur :** Inscription, authentification (JWT), modification et suppression de compte.
* **Logique Métier :** Création des `Processors` (User et Music) pour le traitement des données.
* **Fonctionnalités :** 
    * Système de mise en favoris (API & Front).
    * Affichage de la liste des musiques favorites (API & Front).
    * Création et suppression de musique (API & Front).
    * Intégration du connexion avec **MyAvatar** pour les profils.
    * Critéres d'avis additionels (et l'edition d'avis)
    * Affichage de la liste des reviews par utilisateur.
    * Système de récupération de mot de passe : Envoi automatique d'un e-mail sécurisé lors d'une demande de réinitialisation de mot de passe oublié.
### 🎨 Yanhis 
* **Identité Visuelle :** Design global du site, ergonomie et refactorisation du code frontend.
* **Expérience Utilisateur :** 
    * Page d'accueil (concept du site) et page de détail d'une musique.
    * Gestion des messages flash (Success/Failure) et des événements.
    * Affichage des critiques sur la page d'une musique.
* **Spotify & Data :** * Création du fichier `utils` pour les méthodes de l'API Spotify.
    * Gestion de la popularité dynamique des musiques.
* **Recherche & Listes :** * Barre de recherche et affichage des 20 musiques les plus récentes.

### ⚙️ Andrea 
* **Modélisation :** Création des entités `Music`, `Artist` et `Review` avec leurs relations.
* **Administration :** 
    * Interface de gestion des comptes utilisateurs (Liste/Suppression/Page détail).
    * Interface de validation des musiques (Liste des musiques en attente / Validation).
    * Suppression de n'importe quelle critique.
* **Critiques (Reviews) :** 
    * Logique de création et suppression des critiques (API & Front).
    * Ajout et gestion des critères de notation multiples sur les reviews.
* **Intégrations :** * Lien avec l'API Spotify pour la récupération et l'import des données.
    * Système d'envoi de mail automatique lors de la suppression d'un compte.
---

## 🚀 Installation et Lancement en Local
TO DO
---

## 🔐 Comptes de Test
Utilisateur admin : 
- email : admin@gmail.com
- mdp : MDPadmin

Utilisateur basique :
- email : basicuser@gmail.com
- mdp : MDPbasicuser
---

## ⚙️ Fonctionnement technique

### Sécurité & Permissions (Voters)
Nous avons utilisé des **Voters** pour une gestion fine des droits :
* **UserVoter :** L'email est l'identifiant unique et immuable. Le login (pseudo) est modifiable. Un admin peut supprimer un compte (modération) mais ne peut pas modifier les informations privées d'un utilisateur.
* **ReviewVoter :** Seul l'auteur peut modifier sa critique. L'admin possède uniquement un droit de suppression.
* **MusicVoter :** Seul l'admin peut valider la publication d'une musique suggérée par un utilisateur.



### Contrôle des données
* **Groupes de Sérialisation :** Utilisation de `groups` pour restreindre l'écriture de certains champs (ex: `isValidated`) aux seuls administrateurs.
* **Processors :** Utilisation de `MusicProcessor` et `UserProcessor` pour traiter les données complexes avant persistance (hachage mot de passe, forçage d'état).

### Intégrations
* **Spotify API :** Récupération dynamique des musiques, artistes et métadonnées.
* **MyAvatar :** Liaison automatique des photos de profil utilisateur via l'API MyAvatar.
* **Mailer :** Envoi automatique d'un email lors de la suppression d'un compte ou mot de passe oublié.

---

## 🛠 Utilisation de l'API
La documentation complète des routes est disponible via Swagger à l'adresse `/api`.
* `GET /api/music` : Liste des musiques validées.
* `POST /api/music/import/spotify/{id}` : Importation d'un titre.
* `PATCH /api/users/{id}/favorites` : Gestion de la liste de favoris.

---

## 💬 Commentaires supplémentaires
