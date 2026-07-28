# Manuel utilisateur — Vite et Gourmand

> **Livrable ECF** — Présentation de l’application et parcours de test.  
> À exporter en PDF pour la remise (`01-manuel-utilisateur.pdf`).

---

## 1. Présentation

**Vite et Gourmand** est une application web mobile first destinée à un traiteur événementiel basé à Bordeaux. Elle permet :

- de découvrir les menus (filtres, détail, galerie) ;
- de passer commande en ligne (calcul du prix et de la distance de livraison) ;
- de gérer son compte client (commandes, profil, avis) ;
- aux employés de traiter les commandes, le catalogue et les avis ;
- à l’administrateur de piloter les stats et les comptes employés.

**URL de production :** https://vite-et-gourmand.anteusweb.com

---

## 2. Rôles

| Rôle | Ce qu’il peut faire |
|------|---------------------|
| Visiteur | Accueil, menus, contact, pages légales, inscription / connexion |
| Client | Commander, suivre / modifier / annuler, laisser un avis |
| Employé | Commandes, menus/plats, horaires, modération des avis |
| Administrateur | Tout l’employé + dashboard, stats, gestion des employés |

---

## 3. Identifiants de test

Mot de passe commun : **`Test@123456`**

| Rôle | E-mail |
|------|--------|
| Client | `marie@example.com` |
| Client (avis possible) | `paul@example.com` |
| Employé | `julie@vitegourmand.fr` |
| Administrateur | `jose@vitegourmand.fr` |

---

## 4. Parcours à tester

### 4.1 Visiteur
1. Ouvrir l’accueil → bandeau, présentation Julie & José, avis.  
2. **Nos Menus** → filtrer → ouvrir un détail.  
3. **Contact** → envoyer un message (mail simulé ou SMTP selon config).  
4. Pages légales (footer).

### 4.2 Inscription / connexion
1. Créer un compte → e-mail de confirmation.  
2. Confirmer le lien → se connecter.  
3. Tester « Mot de passe oublié ».

### 4.3 Commande (client)
1. Se connecter → Commander depuis un menu.  
2. Renseigner date, personnes, adresse → vérifier prix / distance.  
3. Accepter les CGV → confirmer.  
4. Retrouver la commande dans **Mon compte**.

### 4.4 Mon compte
1. Détail commande → modifier / annuler si encore autorisé.  
2. Après livraison (jeu de données `paul`) → laisser un avis.  
3. Profil, changement de mot de passe.

### 4.5 Employé
1. Connexion `julie@…` → **Espace employé**.  
2. Changer le statut d’une commande, matériel.  
3. Menus : créer / photos / visibilité (≥ 3 plats).  
4. Modérer un avis.

### 4.6 Administrateur
1. Connexion `jose@…` → **Administration**.  
2. Stats, sync Mongo (si configuré).  
3. Créer / désactiver un employé.

---

## 5. Schémas de parcours

Voir le document joint **Organigramme des parcours** :  
[`../PARCOURS-SITE.pdf`](../PARCOURS-SITE.pdf)

---

## 6. En cas de problème

| Symptôme | Piste |
|----------|--------|
| Page blanche / 404 | DocumentRoot doit pointer vers `public/` |
| Erreur BDD | Vérifier `config/database.local.php` |
| Mail non reçu | Mode simulation → fichiers dans `logs/mails/` |
| Site bloqué au travail | Filtre proxy entreprise (catégorie Uncategorized) — tester hors réseau pro |

---

*À compléter : captures d’écran des écrans clés (accueil, menus, commande, espaces).*
