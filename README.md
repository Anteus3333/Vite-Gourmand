# Vite et Gourmand

Application web **mobile first** pour le traiteur événementiel *Vite et Gourmand* (Bordeaux).  
Projet **ECF Studi** — Développeur web et web mobile : analyse des besoins, maquettes, développement MVC, règles métier, sécurité et déploiement.

| | |
|---|---|
| **Application en ligne** | [https://vite-et-gourmand.anteusweb.com](https://vite-et-gourmand.anteusweb.com) |
| **Dépôt GitHub** | [Anteus3333/Vite-Gourmand](https://github.com/Anteus3333/Vite-Gourmand) |
| **Gestion de projet** | [Trello — Vite et Gourmand (ECF)](https://trello.com/invite/b/6a68f89e1bae43bcad7e1604/ATTI888f7da60bbb9a543d0aaaed96e78728AA5D0E1A/vite-et-gourmand-ecf) |

---

## Sommaire

- [Stack technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation avec Docker (recommandé)](#installation-avec-docker-recommandé)
- [Installation locale Laragon (alternative)](#installation-locale-laragon-alternative)
- [Accès aux données (pattern Repository)](#accès-aux-données-pattern-repository)
- [Configuration](#configuration)
- [Lancement](#lancement)
- [Comptes de test](#comptes-de-test)
- [Structure du projet](#structure-du-projet)
- [Base de données](#base-de-données)
- [Routes principales](#routes-principales)
- [Fonctionnalités](#fonctionnalités)
- [Sécurité](#sécurité)
- [E-mails](#e-mails)
- [Git et branches](#git-et-branches)
- [Déploiement](#déploiement)
- [Documentation du rapport](#documentation-du-rapport)
- [Scripts utiles](#scripts-utiles)

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Back-end | PHP 8+ — architecture **MVC maison** (sans framework) |
| Base de données | **MySQL / MariaDB** (source de vérité) |
| Stats admin (ECF) | **MongoDB Atlas** optionnel (sync depuis MySQL) |
| Front-end | HTML5, CSS3 (Flexbox / Grid, mobile first), JavaScript vanilla |
| Serveur | Apache + `mod_rewrite` (**Docker** en local ECF, Infomaniak en prod ; Laragon en alternative) |
| Authentification | Sessions PHP, mots de passe hashés (`password_hash` / bcrypt) |
| Sécurité | CSRF, échappement XSS, limitation des tentatives de connexion, AuthGuard par rôle |
| Dépendances | Composer (`mongodb/mongodb` pour les stats) |

---

## Prérequis

### Avec Docker (recommandé pour l’ECF)

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows / macOS) ou Docker Engine + Compose
- **Arrêter Laragon** avant de démarrer (conflit possible sur les ports **80** et **3306**)

### Sans Docker (Laragon)

- **PHP** 8.1+ (extensions `pdo_mysql`, `session`, `gd` recommandée)
- **MySQL** ou **MariaDB**
- **Apache** avec `mod_rewrite`
- **Composer**
- [Laragon](https://laragon.org/) ou équivalent

---

## Installation avec Docker (recommandé)

Un nouveau développeur clone le dépôt et lance la stack complète (Apache/PHP 8.2 + MySQL 8).

> **Avant de démarrer :** arrêter Laragon (conflit possible sur les ports **80** et **3306**).  
> **Premier build :** `docker compose up -d --build` peut prendre plusieurs minutes (téléchargement des images + compilation PHP / MongoDB).

```bash
git clone https://github.com/Anteus3333/Vite-Gourmand.git
cd Vite-Gourmand
docker compose up -d --build
```

| Service | Rôle | Accès |
|---------|------|--------|
| `web` | PHP 8.2 + Apache (`DocumentRoot` = `public/`) | [http://localhost:8080](http://localhost:8080) |
| `db` | MySQL 8 — schéma + données de test au 1er démarrage | `127.0.0.1:3306` |

Identifiants MySQL (conteneur) : utilisateur `vite` / mot de passe `vite` (root : `root`).  
La connexion PHP utilise les variables d’environnement `DB_*` définies dans `docker-compose.yml` (pas besoin de `database.local.php`).

**HeidiSQL** (comme avec Laragon) : créer une session dédiée (ex. `Docker.MySQL`) → hôte `127.0.0.1`, port `3306`, user `vite` / mot de passe `vite` (ou `root` / `root`).

Arrêt :

```bash
docker compose down
```

Réinitialiser la base (recrée les volumes + réimporte `sql/`) :

```bash
docker compose down -v
docker compose up -d --build
```

Fichiers Docker versionnés : `Dockerfile`, `docker-compose.yml`, `docker/entrypoint.sh`.

> **Node.js** : non inclus dans Compose — le front est en HTML/CSS/JS vanilla (pas de build Vite/Webpack).

E-mails / MongoDB Atlas : mêmes options qu’en local classique (voir ci-dessous).

---

## Installation locale Laragon (alternative)

### 1. Cloner le dépôt

```bash
git clone https://github.com/Anteus3333/Vite-Gourmand.git
cd Vite-Gourmand
```

Sous Laragon, placer le projet dans `c:\laragon\www\` (ex. `Studi_ECF`).

### 2. Dépendances PHP (optionnel mais recommandé)

```bash
composer install
```

### 3. Créer et peupler la base MySQL

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS vite_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root vite_gourmand < sql/schema.sql
mysql -u root vite_gourmand < sql/donnees_test.sql
```

- `sql/schema.sql` — structure complète des tables  
- `sql/donnees_test.sql` — jeux de données (menus, utilisateurs, commandes, avis…)

### 4. Configuration base de données

Créer `config/database.local.php` (non versionné) si les identifiants diffèrent des défauts Laragon :

```php
<?php
return [
    'host'     => 'localhost',
    'db_name'  => 'vite_gourmand',
    'username' => 'root',
    'password' => '',
];
```

### 5. E-mails (optionnel)

Par défaut, les e-mails sont **simulés** dans `logs/mails/`.  
Pour un envoi réel (Gmail SMTP), créer `config/gmail.local.php` à partir des réglages de `config/gmail.php` (mot de passe d’application Google).

### 6. MongoDB (optionnel — stats admin)

Voir `config/mongodb.local.example.php` → `config/mongodb.local.php`.  
Sans Mongo, le tableau de bord admin utilise MySQL.

---

## Accès aux données (Model → Repository)

Flux : **Controller → Model (métier) → Repository (SQL / Mongo)**.

| Couche | Fichiers | Rôle |
|--------|----------|------|
| Models | `src/Models/*Model.php` | Règles métier, API utilisée par les Controllers |
| Repositories SQL | `src/Repositories/*Repository.php` | Requêtes MySQL uniquement (PDO) |
| Repository NoSQL | `src/Repositories/CommandeStatsRepository.php` | Stats commandes (MongoDB Atlas) |
| Connexion SQL | `src/Services/SqlDatabase.php` | PDO MySQL centralisé |
| Connexion NoSQL | `src/Services/MongoDatabase.php` | Client MongoDB Atlas |

Exemple : `$this->utilisateurModel->findByEmail($email)` → `UtilisateurRepository` exécute le SQL.

---

## Configuration

| Fichier | Rôle |
|---------|------|
| `config/database.php` + `database.local.php` | Paramètres MySQL (ou variables `DB_*` sous Docker) |
| `Dockerfile` + `docker-compose.yml` | Stack locale Apache/PHP/MySQL |
| `src/Services/SqlDatabase.php` | Connexion PDO MySQL |
| `src/Models/` | Couche métier (Controllers → Models) |
| `src/Repositories/` | Accès données SQL + NoSQL (Models → Repositories) |
| `config/gmail.php` + `gmail.local.php` | SMTP Gmail / simulation |
| `src/Services/GmailMailer.php` | Envoi des e-mails |
| `config/commandeconfig.php` | Tarifs livraison, délais, workflow statuts |
| `config/mongodb.php` + `mongodb.local.php` | Stats Mongo (optionnel) |
| `config/turnstile.php` + `turnstile.local.php` | Cloudflare Turnstile (anti-spam contact, optionnel) |

> **Ne jamais versionner** les fichiers `*.local.php` (secrets).

---

## Lancement

**Docker :** après `docker compose up -d --build` → [http://localhost:8080](http://localhost:8080)

**Laragon :** démarrer Apache + MySQL, puis ouvrir **http://localhost/Studi_ECF/public/**

Le front controller est `public/index.php`. En production, le **DocumentRoot** doit pointer vers `public/`.

---

## Comptes de test

Mot de passe commun : **`Test@123456`**

| Rôle | E-mail | Accès |
|------|--------|--------|
| Client | `marie@example.com` | `/mon-compte` — commandes, profil, avis |
| Client | `paul@example.com` | Commande terminée sans avis (`CMD-20260510-002`) |
| Client | `sophie@example.com` | Espace client |
| Employé | `julie@vitegourmand.fr` | `/espace-employe` |
| Administrateur | `jose@vitegourmand.fr` | `/admin` (+ droits employé) |

---

## Structure du projet

```
Vite-Gourmand/
├── public/                 # Seul dossier exposé au navigateur
│   ├── index.php           # Front controller
│   ├── css/  js/  images/
├── src/
│   ├── Controllers/        # Auth, Menu, Commande, Compte, Employé, Admin…
│   ├── Models/             # Métier (API des Controllers)
│   ├── Repositories/       # SQL (PDO) + NoSQL (Mongo stats)
│   ├── Views/              # Templates PHP
│   ├── Services/           # SqlDatabase, MongoDatabase, CSRF, AuthGuard, mails…
│   └── Router.php
├── config/
├── sql/                    # schema + fixtures (import auto sous Docker)
├── docker/                 # entrypoint Apache/PHP
├── Dockerfile              # Image PHP 8.2 + Apache
├── docker-compose.yml      # Services web + MySQL
├── docs/                   # Déploiement, parcours, rapport ECF
├── scripts/                # sync_stats_mongo.php (stats admin Mongo)
├── composer.json
└── README.md
```

---

## Base de données

Fichiers fournis dans `sql/` :

| Fichier | Usage |
|---------|--------|
| `schema.sql` | Création des tables |
| `donnees_test.sql` | Jeu de données de démonstration |

---

## Routes principales

| URL | Description |
|-----|-------------|
| `/` | Accueil |
| `/menus`, `/menu/{id}` | Catalogue et détail |
| `/commande` | Passer commande (connecté) |
| `/login`, `/inscription` | Authentification |
| `/mon-compte` | Espace client |
| `/espace-employe` | Espace employé |
| `/admin` | Administration |
| `/contact` | Contact |
| Pages légales | Mentions, CGV, confidentialité, accessibilité |

Cartographie des parcours : voir le manuel d’utilisation PDF.

---

## Fonctionnalités

### Visiteur / client
- Catalogue menus (filtres AJAX), détail, commande avec calcul prix / distance
- Inscription avec confirmation e-mail, connexion, mot de passe oublié
- Suivi, modification et annulation de commande (selon délai / statut)
- Avis après prestation livrée (modération employé)

### Employé
- Workflow commandes (statuts, matériel, annulation motivée)
- CRUD menus / plats (photos, galerie auto, visibilité si ≥ 3 plats)
- Horaires (footer), modération des avis

### Administrateur
- Dashboard (stats MySQL ou Mongo), sync MySQL → Mongo
- Gestion des comptes employé
- Même catalogue et commandes que l’espace employé

---

## Sécurité

- Mots de passe **bcrypt**
- Jetons **CSRF** sur formulaires sensibles
- Échappement **XSS** (`htmlspecialchars`)
- Limitation des **tentatives de connexion**
- Contrôle d’accès par rôle (**AuthGuard**)
- Upload images contrôlé (MIME, taille, ré-encodage GD)
- Formulaire contact : honeypot, rate-limit, **Cloudflare Turnstile** (si activé)

---

## E-mails

Sans SMTP : archivage dans `logs/mails/`.  
Avec Gmail SMTP (`gmail.local.php`) : envoi réel.

Déclencheurs : confirmation d’inscription, bienvenue, commande, reset MDP, contact, avis, notifications employé / matériel.

---

## Git et branches

Conformément aux consignes ECF :

| Branche | Rôle |
|---------|------|
| `main` | Version stable (production) |
| `dev` | Intégration / développement (équivalent *development*) |
| `feature/*` | Une branche par fonctionnalité, fusionnée dans `dev` après tests |

Flux : `feature/*` → `dev` (tests) → `main`.

Fichiers exclus (`.gitignore`) : `config/*.local.php`, `logs/`, `vendor/` selon config, archives lourdes éventuelles.

---

## Déploiement

L’installation locale est décrite dans la section **Installation locale** ci-dessus  
(DocumentRoot = dossier `public/`).

**Production :** Infomaniak — https://vite-et-gourmand.anteusweb.com  
(configuration `database.local.php` / `gmail.local.php` sur le serveur, hors dépôt).

---

## Documentation du rapport (PDF)

Livrables ECF versionnés dans `docs/` (PDF uniquement) :

| Document | Fichier |
|----------|---------|
| Manuel d’utilisation | [`docs/rapport_ECF/01-SAGD - manuel-utilisateur.pdf`](docs/rapport_ECF/01-SAGD%20-%20manuel-utilisateur.pdf) |
| Charte graphique | [`docs/rapport_ECF/02-SAGD - charte-graphique.pdf`](docs/rapport_ECF/02-SAGD%20-%20charte-graphique.pdf) |
| Gestion de projet | [`docs/rapport_ECF/03-SAGD - gestion-de-projet.pdf`](docs/rapport_ECF/03-SAGD%20-%20gestion-de-projet.pdf) |
| Documentation technique | [`docs/rapport_ECF/04-SAGD - documentation-technique.pdf`](docs/rapport_ECF/04-SAGD%20-%20documentation-technique.pdf) |

Les brouillons (HTML/MD), captures et scripts restent en local (non versionnés) ; seuls les PDF livrables sont commités.

---

## Scripts utiles

Synchronisation des statistiques MySQL → MongoDB (admin) :

```bash
php scripts/sync_stats_mongo.php
```

---

## Auteur & contexte

Projet **ECF — Développeur web et web mobile** (Studi)  
Entreprise fictive : **Vite et Gourmand** — Julie & José, traiteurs à Bordeaux depuis 25 ans.
