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
- [Installation locale (pas à pas)](#installation-locale-pas-à-pas)
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
| Serveur | Apache + `mod_rewrite` (Laragon en local, Infomaniak en prod) |
| Authentification | Sessions PHP, mots de passe hashés (`password_hash` / bcrypt) |
| Sécurité | CSRF, échappement XSS, limitation des tentatives de connexion, AuthGuard par rôle |
| Dépendances | Composer (`mongodb/mongodb` pour les stats) |

---

## Prérequis

- **PHP** 8.0+ (extensions `pdo_mysql`, `session`, `gd` recommandée pour l’upload d’images)
- **MySQL** ou **MariaDB**
- **Apache** avec `mod_rewrite`
- **Composer** (pour MongoDB / stats admin — optionnel si stats MySQL seules)
- En local : [Laragon](https://laragon.org/), XAMPP ou équivalent

---

## Installation locale (pas à pas)

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
Pour un envoi réel (Gmail SMTP), créer `config/mail.local.php` à partir des réglages de `config/mail.php` (mot de passe d’application Google).

### 6. MongoDB (optionnel — stats admin)

Voir `config/mongodb.local.example.php` → `config/mongodb.local.php`.  
Sans Mongo, le tableau de bord admin utilise MySQL.

---

## Configuration

| Fichier | Rôle |
|---------|------|
| `config/database.php` + `database.local.php` | Connexion MySQL |
| `config/mail.php` + `mail.local.php` | SMTP / simulation |
| `config/commande.php` | Tarifs livraison, délais, workflow statuts |
| `config/mongodb.php` + `mongodb.local.php` | Stats Mongo (optionnel) |

> **Ne jamais versionner** les fichiers `*.local.php` (secrets).

---

## Lancement

1. Démarrer Laragon (Apache + MySQL)
2. Ouvrir : **http://localhost/Studi_ECF/public/**  
   (adapter le nom de dossier si besoin)

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
│   ├── Models/             # Accès BDD (PDO)
│   ├── Views/              # Templates PHP
│   ├── Services/           # Mailer, CSRF, AuthGuard, upload images, Mongo…
│   └── Router.php
├── config/
├── sql/                    # schema + fixtures + migrations
├── docs/                   # Déploiement, parcours, rapport ECF
├── scripts/                # Migrations, optimisation images, sync Mongo
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

Cartographie détaillée : [`docs/PARCOURS-SITE.pdf`](docs/PARCOURS-SITE.pdf)

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

---

## E-mails

Sans SMTP : archivage dans `logs/mails/`.  
Avec Gmail SMTP (`mail.local.php`) : envoi réel.

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
(configuration `database.local.php` / `mail.local.php` sur le serveur, hors dépôt).

---

## Documentation du rapport (PDF)

Livrables ECF versionnés dans `docs/` (PDF uniquement) :

| Document | Fichier |
|----------|---------|
| Manuel d’utilisation | [`docs/rapport/01-manuel-utilisateur.pdf`](docs/rapport/01-manuel-utilisateur.pdf) |
| Organigramme des parcours | [`docs/PARCOURS-SITE.pdf`](docs/PARCOURS-SITE.pdf) |
| Charte graphique | `docs/rapport/02-charte-graphique.pdf` *(à venir)* |
| Gestion de projet | `docs/rapport/03-gestion-de-projet.pdf` *(à venir)* |
| Documentation technique | `docs/rapport/04-documentation-technique.pdf` *(à venir)* |

Les brouillons Markdown / captures / scripts restent en local (non versionnés).

---

## Scripts utiles

```bash
php scripts/run_migration.php chemin/vers/fichier.sql
php scripts/optimize-images.php
php scripts/sync_stats_mongo.php
```

---

## Auteur & contexte

Projet **ECF — Développeur web et web mobile** (Studi)  
Entreprise fictive : **Vite et Gourmand** — Julie & José, traiteurs à Bordeaux depuis 25 ans.
