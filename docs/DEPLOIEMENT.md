# Déploiement — Vite et Gourmand

Guide pour mettre le site en ligne (hébergement PHP + MySQL) après clonage depuis GitHub.

---

## Prérequis hébergeur

| Besoin | Détail |
|--------|--------|
| PHP | 8.0+ avec `pdo_mysql`, `session` |
| MySQL / MariaDB | 1 base dédiée |
| Apache | `mod_rewrite` activé |
| HTTPS | Recommandé (Let's Encrypt) |

Hébergeurs compatibles : **AlwaysData**, **o2switch**, **PlanetHoster**, **InfinityFree** (gratuit, plus limité), etc.

---

## 1. Dépôt GitHub (local)

```bash
cd c:\laragon\www\Studi_ECF
git init
git add .
git commit -m "Initial commit — application Vite et Gourmand (ECF Studi)"
git branch -M main
git branch dev
```

Créer un dépôt **public** sur GitHub (ex. `vite-et-gourmand-ecf`), puis :

```bash
git remote add origin https://github.com/VOTRE_COMPTE/vite-et-gourmand-ecf.git
git push -u origin main
git push -u origin dev
```

> Branches ECF : `main` (stable) et `dev` (développement).

---

## 2. Base de données en production

1. Créer une base MySQL depuis le panneau hébergeur
2. Importer **`sql/schema.sql`** puis **`sql/donnees_test.sql`** (phpMyAdmin ou ligne de commande)
3. Noter : hôte, nom de BDD, utilisateur, mot de passe

---

## 3. Fichiers sur le serveur

### Option A — DocumentRoot = dossier `public/` (recommandé)

Structure sur le serveur :

```
/home/user/vite-gourmand/
├── config/
├── public/          ← DocumentRoot Apache
├── src/
├── sql/
└── ...
```

Le dossier **`public/`** doit être la racine web. Les dossiers `config/`, `src/`, `logs/` ne doivent **pas** être accessibles directement par URL.

### Option B — Racine = projet entier

Si l'hébergeur ne permet pas de choisir le DocumentRoot, uploader tout le projet : le fichier **`.htaccess`** à la racine redirige vers `public/`.

---

## 4. Configuration production (hors Git)

Sur le serveur, créer deux fichiers **non versionnés** :

### `config/database.local.php`

```php
<?php
return [
    'host'     => 'mysqlXXX.hosting.com',  // fourni par l'hébergeur
    'db_name'  => 'nom_bdd',
    'username' => 'user_bdd',
    'password' => 'mot_de_passe_bdd',
];
```

### `config/mail.local.php`

Copier depuis `config/mail.local.example.php` et renseigner le mot de passe d'application Gmail :

```php
<?php
return [
    'smtp' => [
        'enabled'  => true,
        'host'     => 'smtp.gmail.com',
        'username' => 'vitegourmand322@gmail.com',
        'password' => 'VOTRE_MOT_DE_PASSE_APPLICATION',
    ],
];
```

Créer le dossier **`logs/mails/`** en écriture (chmod 755 ou 775 selon l'hébergeur).

---

## 5. Vérifications après mise en ligne

- [ ] Page d'accueil s'affiche
- [ ] Connexion client / employé / admin
- [ ] Filtres menus (AJAX)
- [ ] Formulaire contact → mail reçu
- [ ] `/admin` — graphiques stats
- [ ] `/espace-employe/commandes` — filtre client
- [ ] Mettre à jour l'**hébergeur** dans `src/Views/legal/mentions-legales.php`

---

## 6. Comptes de test

Mot de passe : **`Test@123456`**

| Rôle | E-mail |
|------|--------|
| Client | `marie@example.com` |
| Employé | `julie@vitegourmand.fr` |
| Admin | `jose@vitegourmand.fr` |

---

## 7. Mise à jour du site

```bash
git checkout main
git pull origin main
# uploader les fichiers modifiés (FTP, SFTP, ou git pull sur le serveur si SSH)
```

Ne jamais écraser `config/database.local.php` ni `config/mail.local.php` sur le serveur lors d'un déploiement.

---

## Dépannage

| Problème | Piste |
|----------|--------|
| 404 sur toutes les pages sauf accueil | `mod_rewrite` désactivé ou `.htaccess` ignoré |
| CSS/JS absents | Vérifier que l'URL contient bien `/public/` ou que le DocumentRoot est correct |
| Erreur BDD | Vérifier `database.local.php` et droits utilisateur MySQL |
| Mails non envoyés | Vérifier `mail.local.php`, mot de passe d'application Gmail |
| Page blanche | Activer les logs PHP côté hébergeur |
