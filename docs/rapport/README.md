# Rapport ECF — Vite et Gourmand

Plan du livrable aligné sur les consignes Studi (**Objectif & Livrables**).  
Chaque section ci-dessous sera rédigée progressivement ; les PDF finaux seront exportés pour le dépôt / la remise.

## Liens à fournir (page de garde / README)

| Livrable | Lien | Statut |
|----------|------|--------|
| Dépôt GitHub **public** | https://github.com/Anteus3333/Vite-Gourmand | Repo actuel **privé** → à passer en public avant remise |
| Application déployée | https://vite-et-gourmand.anteusweb.com | OK |
| Outil de gestion de projet | [Trello — Vite et Gourmand (ECF)](https://trello.com/invite/b/6a68f89e1bae43bcad7e1604/ATTI888f7da60bbb9a543d0aaaed96e78728AA5D0E1A/vite-et-gourmand-ecf) (privé + lien d’invitation) | OK |

---

## Plan du rapport

### 1. Manuel utilisateur (PDF)
Fichier cible : `docs/rapport/01-manuel-utilisateur.md` → export PDF  
- Présentation de l’application  
- Parcours visiteur, client, employé, admin  
- Identifiants de test (tous les rôles)  
- *S’appuyer sur* `docs/PARCOURS-SITE.pdf`

### 2. Charte graphique (PDF)
Fichier cible : `docs/rapport/02-charte-graphique.md` → export PDF  
- Palette de couleurs (`--primary`, `--secondary`…)  
- Typographies  
- 3 maquettes **desktop** (wireframe + haute fidélité)  
- 3 maquettes **mobile** (wireframe + haute fidélité)

### 3. Gestion de projet
Fichier cible : `docs/rapport/03-gestion-de-projet.md`  
- Méthode (Kanban / sprints…)  
- Lien vers l’outil  
- Organisation des tâches, priorisation, suivi

### 4. Documentation technique
Fichier cible : `docs/rapport/04-documentation-technique.md`

| Sous-partie | Contenu |
|-------------|---------|
| Réflexions techniques | Choix PHP MVC maison, MySQL, Mongo optionnel, JS vanilla, Apache |
| Environnement | Laragon local, Infomaniak prod, Composer, Git |
| Modèle de données | MCD / schéma SQL (`sql/schema.sql`) |
| UML | Cas d’utilisation + diagrammes de séquence |
| Déploiement | Étapes Infomaniak + DocumentRoot `public/` |

### 5. Sécurité
Intégré à la doc technique ou chapitre dédié : CSRF, bcrypt, AuthGuard, XSS, brute-force, upload images.

---

## Ordre de rédaction recommandé

1. Manuel utilisateur (rapide grâce aux parcours déjà cartographiés)  
2. Doc technique — choix techno + déploiement  
3. Modèle de données + UML  
4. Charte graphique (maquettes à intégrer / produire)  
5. Gestion de projet (selon l’outil réellement utilisé)

---

## GitHub — section About (à coller dans Settings → General)

**Description :**
```
Traiteur événementiel Bordeaux — app PHP MVC (menus, commande, espaces client / employé / admin). ECF Studi.
```

**Website :**
```
https://vite-et-gourmand.anteusweb.com
```

**Topics (suggestions) :**
```
php, mysql, mvc, apache, ecommerce, catering, studi, ecf, javascript, css
```

Passer le dépôt en **Public** avant la remise (consigne).
