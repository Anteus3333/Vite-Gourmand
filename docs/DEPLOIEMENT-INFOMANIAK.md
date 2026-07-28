# Déploiement Infomaniak — vite-et-gourmand.anteusweb.com

Guide pas à pas pour publier le projet sur ton hébergement Infomaniak (sous-domaine de **anteusweb.com**).

**URL cible :** https://vite-et-gourmand.anteusweb.com

---

## Vue d'ensemble

| Étape | Où | Action |
|-------|-----|--------|
| 1 | Manager Infomaniak | Créer la base MySQL |
| 2 | phpMyAdmin | Importer `schema.sql` + `donnees_test.sql` |
| 3 | FTP / SFTP | Uploader les fichiers du projet |
| 4 | Manager Infomaniak | Pointer le site vers le dossier `public/` |
| 5 | FTP | Créer `config/database.local.php` et `config/mail.local.php` |
| 6 | Navigateur | Tester le site |

---

## 1. Base de données MySQL

1. Manager Infomaniak → ton **Hébergement Web** → **Bases de données**
2. **Créer une base de données** (ex. `vite_gourmand`)
    - se fait sur interface infomaniak
3. Noter ces 4 informations :
   - **Hôte** (ex. `mysqlXXX.hosting.infomaniak.com`)
   - **Nom de la base**
   - **Utilisateur**
   - **Mot de passe**

4. Ouvrir **phpMyAdmin** (lien depuis le Manager)
5. **Cliquer sur votre base** dans le menu de gauche (ex. `gkhl_vite_gourmand`)
6. Onglet **Importer** → fichier **`sql/schema.sql`** → Exécuter
7. Répéter avec **`sql/donnees_test.sql`** (base toujours sélectionnée)

> Installation neuve : uniquement ces deux fichiers.  
> Si la base existe déjà en prod et que vous voulez repartir proprement :  
> sauvegarder → vider / recréer la base → réimporter `schema.sql` puis `donnees_test.sql`.  
> Penser aussi à uploader `public/images/menus/*.jpg` et `public/images/plats/*.jpg`.

---

## 2. Accès FTP / SFTP

1. Manager → site **vite-et-gourmand.anteusweb.com** → **Créer un accès FTP / SSH**
2. Noter : hôte FTP (ex. `XXX.ftp.infomaniak.com`), utilisateur, mot de passe
3. Client recommandé : **FileZilla** ou **WinSCP**

### Dossier cible sur le serveur

Par défaut Infomaniak crée :

```
/sites/vite-et-gourmand.anteusweb.com/
```

(Vérifie le chemin exact dans **Informations** du site, section développée.)

---

## 3. Uploader les fichiers

Uploader **tout le projet** depuis `c:\laragon\www\Studi_ECF\` **sauf** :

- `.git/` (dossier Git — inutile sur le serveur)
- `logs/` (sera recréé)
- `config/mail.local.php` (tu le créeras directement sur le serveur)

**À uploader :**

```
/sites/vite-et-gourmand.anteusweb.com/
├── config/
├── docs/
├── public/
├── scripts/
├── sql/
├── src/
├── vendor/            ← obligatoire pour MongoDB (Composer)
├── composer.json
├── composer.lock
├── .htaccess          (optionnel si racine = public/)
├── README.md
└── ...
```

Ne **pas** uploader / écraser :

- `config/database.local.php`, `config/mail.local.php`, `config/mongodb.local.php` déjà en place
- `.git/`, `logs/`
---

## 4. Pointer la racine web vers `public/` (important)

Pour que `config/` et `src/` ne soient **pas** accessibles par URL :

1. Manager → **vite-et-gourmand.anteusweb.com**
2. **Paramètres avancés** → **Gérer** (emplacement du site)
3. Modifier la cible du site pour ajouter **`/public`** à la fin du chemin

Exemple :

```
/sites/vite-et-gourmand.anteusweb.com/public
```

4. **Enregistrer**

> [FAQ Infomaniak — Modifier le dossier d'un site](https://www.infomaniak.com/fr/support/faq/963/modifier-le-dossier-dun-site-web)

**Alternative** si tu ne changes pas la cible : laisser la racine sur le projet entier — le fichier **`.htaccess`** à la racine redirige déjà vers `public/`.

---

## 5. Fichiers de config production (sur le serveur)

Créer via FTP ou l'éditeur du Manager :

### `config/database.local.php`

```php
<?php
return [
    'host'     => 'mysqlXXX.hosting.infomaniak.com',  // hôte Infomaniak
    'db_name'  => 'xxxxx_vite_gourmand',              // nom exact de la BDD
    'username' => 'xxxxx_user',                       // utilisateur MySQL
    'password' => 'TON_MOT_DE_PASSE_BDD',
];
```

### `config/mail.local.php`

Copier le contenu de ton fichier local (même mot de passe d'application Gmail) :

```php
<?php
return [
    'smtp' => [
        'enabled'  => true,
        'host'     => 'smtp.gmail.com',
        'username' => 'vitegourmand322@gmail.com',
        'password' => 'TON_MOT_DE_PASSE_APPLICATION',
    ],
];
```

### `config/mongodb.local.php` (stats NoSQL / ECF)

Même URI Atlas qu’en local (copier depuis `config/mongodb.local.php` local) :

```php
<?php
return [
    'uri'        => 'mongodb+srv://USER:PASS@cluster0.xxxxx.mongodb.net/?retryWrites=true&w=majority',
    'database'   => 'vite_gourmand_stats',
    'collection' => 'commandes_stats',
];
```

**Network Access Atlas** : autoriser l’IP sortante d’Infomaniak  
(ou `0.0.0.0/0` pour la démo ECF).

### Dossier `vendor/` (Composer)

Uploader aussi le dossier **`vendor/`** généré en local (`composer install`),  
sinon MongoDB ne pourra pas se charger.

### Dossier logs

Non requis en production si **`mail.local.php`** est configuré (Gmail actif) : les mails ne sont plus copiés sur le disque.

---

## 5bis. Stats MongoDB en production

1. Uploader les fichiers Mongo (voir liste ci-dessous) + `vendor/`
2. Créer `config/mongodb.local.php` sur le serveur
3. Ouvrir `/admin` → bouton **Sync MySQL → Mongo** (recopie la BDD **prod**)
4. Bascule **MongoDB Atlas** / **MySQL** pour le jury

> **Points critiques Infomaniak :**  
> 1. Extension PHP `mongodb` parfois absente sur mutualisé → message clair + repli MySQL.  
> 2. Si l’extension est là mais **socket timeout** vers `*.mongodb.net:27017` : ouvrir le **port sortant 27017**  
>    (Manager → hébergement → Sécurité → Ouverture de ports) vers les hôtes Atlas,  
>    et vérifier Network Access Atlas (`0.0.0.0/0` ou l’IP sortante Infomaniak).  
> MySQL reste toujours disponible via le bouton bascule (démo jury OK).

### Fichiers liés à Mongo à uploader / mettre à jour

- `composer.json` / `composer.lock` / **`vendor/`**
- `config/mongodb.php` + `config/mongodb.local.php` (créé sur le serveur, pas dans Git)
- `src/Services/MongoDatabase.php`
- `src/Services/StatsMongoService.php`
- `src/Controllers/AdminController.php`
- `src/Controllers/CommandeController.php`
- `src/Controllers/CompteController.php`
- `src/Controllers/Traits/GestionCommandesAvisTrait.php`
- `src/Models/CommandeModel.php`
- `src/Router.php`
- `src/Views/admin/index.php`
- `public/index.php`
- `public/css/style.css`
- `scripts/sync_stats_mongo.php` (optionnel si sync via le bouton admin)

---

## 6. PHP

Ton site est déjà en **PHP 8.1** sur Infomaniak — parfait.

Vérifier dans le Manager que **PHP 8.1** (ou 8.2+) est bien actif pour ce sous-domaine.

---

## 7. Tests après mise en ligne

- [ ] https://vite-et-gourmand.anteusweb.com/ — accueil
- [ ] `/menus` — filtres AJAX
- [ ] Connexion `marie@example.com` / `Test@123456`
- [ ] `/admin` avec `jose@vitegourmand.fr`
- [ ] `/admin` — bascule Mongo / MySQL + Sync
- [ ] Formulaire contact → mail reçu
- [ ] Pas d'accès direct à `https://vite-et-gourmand.anteusweb.com/../config/` (doit échouer)

---

## 8. Mises à jour ultérieures

1. Modifier en local
2. Commit + push sur GitHub (repo privé)
3. Uploader via FTP **uniquement les fichiers modifiés**
4. **Ne jamais écraser** `database.local.php`, `mail.local.php` ni `mongodb.local.php` sur le serveur
5. Après sync de commandes en prod : bouton **Sync MySQL → Mongo** dans `/admin`

---

## Dépannage Infomaniak

| Problème | Solution |
|----------|----------|
| Page « En construction » Infomaniak | Supprimer `index.html` par défaut dans le dossier du site |
| 404 sur `/menus`, `/login`… | Vérifier `public/.htaccess` et que `mod_rewrite` est actif |
| Erreur BDD | Vérifier hôte MySQL Infomaniak (pas `localhost` sauf si indiqué) |
| CSS absents | Racine web doit être `public/` ; vérifier BASE_URL auto |
| 500 Internal Error | Consulter les logs PHP dans le Manager Infomaniak |

---

## Comptes de test

Mot de passe : **`Test@123456`**

| Rôle | E-mail |
|------|--------|
| Client | `marie@example.com` |
| Employé | `julie@vitegourmand.fr` |
| Admin | `jose@vitegourmand.fr` |
