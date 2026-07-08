# Vite et Gourmand

Application web **mobile first** pour le traiteur événementiel *Vite et Gourmand* (Bordeaux).  
Projet réalisé dans le cadre de l'**ECF Studi** — découverte des menus, commande en ligne, espaces client, employé et administrateur.

---

## Sommaire

- [Stack technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Lancement](#lancement)
- [Comptes de test](#comptes-de-test)
- [Structure du projet](#structure-du-projet)
- [Routes principales](#routes-principales)
- [Fonctionnalités](#fonctionnalités)
- [E-mails](#e-mails)
- [Git](#git)
- [Déploiement](#déploiement)
- [Scripts utiles](#scripts-utiles)

---

## Stack technique

| Couche | Technologie |
|--------|-------------|
| Back-end | PHP 8+ (architecture MVC maison, sans framework) |
| Base de données | MySQL / MariaDB — colonne `password` en `VARCHAR(255)` pour bcrypt |
| Front-end | HTML, CSS (Flexbox / Grid), JavaScript vanilla |
| Serveur | Apache + `mod_rewrite` (Laragon en local) |
| Authentification | Sessions PHP, mots de passe hashés (`password_hash` / bcrypt) |
| Sécurité | CSRF, échappement XSS (`htmlspecialchars`), limitation des tentatives de connexion |

---

## Prérequis

- **PHP** 8.0 ou supérieur (extensions `pdo_mysql`, `session`)
- **MySQL** ou **MariaDB**
- **Apache** avec `mod_rewrite` activé
- En local : [Laragon](https://laragon.org/), XAMPP ou équivalent

---

## Installation

### 1. Cloner ou copier le projet

Placer le dossier dans le répertoire web de Laragon, par exemple :

```
c:\laragon\www\Studi_ECF\
```

### 2. Créer la base de données

Depuis un terminal ou HeidiSQL / phpMyAdmin :

```bash
mysql -u root < sql/schema.sql
mysql -u root < sql/donnees_test.sql
```

- `sql/schema.sql` — crée la base `vite_gourmand` et toutes les tables
- `sql/donnees_test.sql` — insère les données de démonstration (menus, utilisateurs, commandes…)

### 3. Configurer la connexion BDD

Copier `config/database.example.php` vers `config/database.local.php` si vos identifiants diffèrent des valeurs par défaut :

```php
return [
    'host'     => 'localhost',
    'db_name'  => 'vite_gourmand',
    'username' => 'root',
    'password' => '',
];
```

> Ne jamais versionner `database.local.php`.

---

## Configuration

### Base de données

Fichiers : `config/database.php` (défauts) + `config/database.local.php` (secrets locaux, optionnel en dev Laragon).

### E-mails (optionnel)

Par défaut, les e-mails sont **simulés** et archivés dans `logs/mails/`.

Adresse officielle du traiteur : **vitegourmand322@gmail.com** (`config/mail.php`).

### Envoi réel (Gmail SMTP)

1. Copier `config/mail.local.example.php` vers `config/mail.local.php`
2. Créer un **mot de passe d'application** Google (compte Google → Sécurité)
3. Renseigner le mot de passe dans `mail.local.php` et activer `enabled => true`

> Ne jamais versionner `mail.local.php` (contient des secrets).

### Règles métier commande

Fichier : `config/commande.php` — tarifs livraison, réductions, workflow des statuts employé.

### Images

Les visuels sont dans `public/images/` (SVG générés via `scripts/gen_images.py`).  
La galerie menus est stockée en BDD (`menu_image`), les photos de plats dans `plat.image`.

Pour régénérer les visuels par défaut :

```bash
py scripts/gen_images.py
```

Si la base existait **avant** l’ajout des images, exécuter aussi :

```bash
php scripts/run_migration.php sql/migration_images.sql
php scripts/fix_plat_images.php
```

Si la base existait **avant** la gestion des comptes employé :

```bash
php scripts/run_migration.php sql/migration_employes.sql
```

Si la base existait **avant** l'annulation employé / suivi matériel :

```bash
php scripts/run_migration.php sql/migration_commande_employe.sql
```

---

## Lancement

### Avec Laragon (URL par défaut)

1. Démarrer Laragon (Apache + MySQL)
2. Ouvrir : **http://localhost/Studi_ECF/public/**

Le point d'entrée unique est `public/index.php`. Le dossier `public/` doit être la racine web (DocumentRoot) ou faire partie de l'URL.

### Virtual host (recommandé en production)

Configurer Apache pour que le DocumentRoot pointe directement vers le dossier `public/`.  
Exemple d'URL : `http://vite-gourmand.local/`

---

## Comptes de test

Mot de passe commun : **`Test@123456`**

| Rôle | E-mail | Accès |
|------|--------|--------|
| Client | `marie@example.com` | Espace client, commandes, avis |
| Client | `paul@example.com` | Idem — possède une commande **terminée** sans avis (`CMD-20260510-002`) |
| Client | `sophie@example.com` | Idem |
| Employé | `julie@vitegourmand.fr` | `/espace-employe` — commandes, menus, plats, horaires, modération avis |
| Administrateur | `jose@vitegourmand.fr` | `/admin` (tableau de bord) + `/espace-employe` — mêmes droits catalogue + stats admin |

---

## Structure du projet

```
Studi_ECF/
├── public/                 # Seul dossier exposé au navigateur
│   ├── index.php           # Front controller
│   ├── .htaccess           # Réécriture d'URL
│   ├── css/
│   ├── js/
│   └── images/             # Bandeau, favicon, menus, plats
├── src/
│   ├── Controllers/        # Logique métier (Home, Auth, Menu, Commande…)
│   ├── Models/             # Accès BDD (PDO)
│   ├── Views/              # Templates PHP
│   ├── Services/           # Mailer, CSRF, AuthGuard, AssetHelper…
│   └── Router.php          # Routeur manuel
├── config/
│   ├── database.php
│   ├── mail.php
│   └── commande.php
├── sql/
│   ├── schema.sql          # Structure BDD
│   └── donnees_test.sql    # Données de démo
├── scripts/
│   ├── run_migration.php   # Exécution de fichiers SQL ponctuels
│   └── gen_images.py       # Génération des visuels SVG par défaut
└── logs/mails/             # Archivage des e-mails (mode dev)
```

---

## Routes principales

| URL | Description |
|-----|-------------|
| `/` | Accueil + avis clients validés |
| `/menus` | Catalogue avec filtres dynamiques (AJAX) |
| `/menu/{id}` | Détail d'un menu |
| `/commande` | Passage de commande (connecté) |
| `/contact` | Formulaire de contact |
| `/login`, `/inscription` | Authentification |
| `/mon-compte` | Espace client (commandes, profil, avis) |
| `/espace-employe` | Gestion commandes, menus, plats, horaires, modération avis |
| `/admin` | Tableau de bord administrateur (stats, alertes stock, comptes employé) |
| `/admin/employes` | Gestion des comptes employé |
| `/mentions-legales`, `/cgv`, `/accessibilite` | Pages légales et accessibilité |

---

## Fonctionnalités

### Visiteur / client
- Parcours menus (filtres sans rechargement)
- Création de compte et connexion sécurisée
- Commande avec calcul automatique du prix (livraison, réduction)
- Suivi de commande, modification et annulation (tant que non acceptée)
- Dépôt d'avis après commande terminée (modération employé)

### Employé
- Validation des commandes et changement de statuts (workflow)
- **Filtre des commandes par statut ou par client**
- **Annulation avec motif et mode de contact client** (e-mail au client)
- **E-mail automatique** au statut « en attente du retour de matériel » (10 j. ouvrés, 600 € — CGV)
- Suivi matériel prêté / restitué
- CRUD menus, association des plats et allergènes
- Gestion des horaires (footer)
- Modération des avis (publier / refuser)

### Administrateur
- Tableau de bord (stats, **graphiques commandes et CA par menu** avec filtres période/menu, alertes stock, comptes employé)
- **Création et désactivation des comptes employé** (e-mail de notification)
- Accès complet à l'espace employé et aux mêmes outils catalogue

### Transversal
- Protection CSRF sur les formulaires sensibles
- Accessibilité RGAA (lien d'évitement, navigation clavier, déclaration `/accessibilite`)
- Mentions légales et CGV

---

## E-mails

En l'absence de configuration SMTP, chaque envoi est enregistré dans :

```
logs/mails/YYYY-MM-DD_HHMMSS_destinataire.txt
```

E-mails déclenchés : bienvenue, confirmation commande, réinitialisation mot de passe, contact, notification avis disponible.

---

## Git

Le projet utilise **Git** avec deux branches :

| Branche | Rôle |
|---------|------|
| `main` | Version stable (production) |
| `dev` | Développement et corrections |

### Première publication sur GitHub

```bash
git init
git add .
git commit -m "Initial commit — application Vite et Gourmand (ECF Studi)"
git branch -M main
git branch dev
git remote add origin https://github.com/VOTRE_COMPTE/NOM_DU_REPO.git
git push -u origin main
git push -u origin dev
```

Fichiers **exclus** du dépôt (`.gitignore`) : `config/database.local.php`, `config/mail.local.php`, `logs/`.

---

## Déploiement

Guide détaillé : **[docs/DEPLOIEMENT.md](docs/DEPLOIEMENT.md)**

Résumé :

1. Créer une base MySQL sur l'hébergeur et importer `sql/schema.sql` + `sql/donnees_test.sql`
2. Uploader le projet (DocumentRoot = dossier **`public/`** de préférence)
3. Créer sur le serveur `config/database.local.php` et `config/mail.local.php`
4. Vérifier les parcours principaux et mettre à jour les mentions légales (hébergeur)

---

## Scripts utiles

Exécuter un fichier SQL ponctuel via PDO :

```bash
php scripts/run_migration.php chemin/vers/fichier.sql
```

---

## Auteur & contexte

Projet **ECF — Développeur web et web mobile**  
Entreprise fictive : **Vite et Gourmand** — Julie & José, traiteurs à Bordeaux depuis 25 ans.
