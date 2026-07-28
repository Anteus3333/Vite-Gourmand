# Gestion de projet — Vite et Gourmand

> **Livrable ECF** — Explication de la gestion de projet + lien vers l’outil.

---

## 1. Outil utilisé

| | |
|---|---|
| **Outil** | **Trello** (plan Free) |
| **Lien du board** | [Vite et Gourmand — ECF](https://trello.com/invite/b/6a68f89e1bae43bcad7e1604/ATTI888f7da60bbb9a543d0aaaed96e78728AA5D0E1A/vite-et-gourmand-ecf) |
| **Méthode** | Kanban léger (Backlog → En cours → À tester → Fait) |
| **Visibilité** | Board **privé** + **lien d’invitation** partagé dans le rapport / README (pas besoin de rendre le tableau public) |

### Pourquoi Trello ?
- Gratuit et suffisant pour un projet ECF  
- Vue Kanban claire pour prioriser et suivre l’avancement  
- Partage simple du lien dans les livrables  
- Compatible avec un workflow Git (`feature/*` → `dev` → `main`)

---

## 2. Organisation du board

### Colonnes

| Colonne | Rôle |
|---------|------|
| **Backlog** | Idées / tâches pas encore démarrées |
| **En cours** | Travail en cours |
| **À tester** | Développé, en validation (parcours, responsive, sécurité) |
| **Fait** | Validé et intégré (`dev` / `main`) |

### Labels suggérés
- `front` · `back` · `bdd` · `sécurité` · `déploiement` · `doc` · `ecf-nosql`

---

## 3. Cartes du projet (à créer dans Trello)

Copier ces cartes (titre + description courte).

### Fonctionnel
1. **Accueil** — présentation, équipe, avis validés  
2. **Catalogue menus** — liste, filtres AJAX, détail, galerie  
3. **Auth** — inscription, confirmation e-mail, connexion, reset MDP  
4. **Commande** — formulaire, prix, livraison km, CGV, mail confirmation  
5. **Espace client** — commandes, modifier/annuler, profil, avis  
6. **Espace employé** — workflow statuts, matériel, menus/plats, horaires, modération avis  
7. **Espace admin** — employés, dashboard, stats Mongo  

### Technique / ECF
8. **BDD MySQL** — `schema.sql` + `donnees_test.sql`  
9. **MongoDB stats** — sync MySQL → Mongo, graphiques admin  
10. **Sécurité** — CSRF, bcrypt, AuthGuard, XSS, brute-force, upload  
11. **Accessibilité RGAA** — skip link, contrastes, pages légales  
12. **Déploiement Infomaniak** — DocumentRoot `public/`, config locale  
13. **Optimisation images** — compression JPG, upload sécurisé  
14. **Documentation** — README, manuel, charte, rapport technique  
15. **Parcours / QA** — organigramme des parcours + tests manuels des rôles  
    *(QA = Quality Assurance = contrôle qualité / validation des parcours)*  

---

## 4. Lien avec Git

| Étape | Action |
|-------|--------|
| Démarrer une carte | Branche `feature/…` depuis `dev` |
| Fin de dev | Merge vers `dev` après tests |
| Carte → **À tester** | Parcours manuels (client / employé / admin) |
| Carte → **Fait** | Intégré ; release vers `main` quand le lot est stable |

---

## 5. Suivi réel du projet

Le suivi a été tenu de façon **pragmatique** :
- priorisation par parcours métier (visiteur → client → employé → admin) ;  
- validation continue sur environnement local (Laragon) puis production Infomaniak ;  
- Trello sert de **vue consolidée** pour le jury et de support à la documentation.

### Difficultés notables (à enrichir si besoin)
- Hébergement mutualisé (DocumentRoot, SMTP, ports Mongo)  
- Filtre proxy entreprise sur le sous-domaine (catégorie Uncategorized)  
- Poids initial des images → batch d’optimisation  

---

## 6. Preuves à joindre au PDF

- [ ] Lien Trello renseigné ci-dessus + dans le `README.md`  
- [ ] Capture d’écran du board (vue d’ensemble)  
- [ ] 2–3 cartes ouvertes en détail (ex. Commande, Mongo, Déploiement)  

---

## 7. Création du board (checklist 5 min)

1. Créer un compte sur [trello.com](https://trello.com) (Free)  
2. **Créer un tableau** : nom `Vite et Gourmand — ECF`  
3. Créer les 4 listes : Backlog, En cours, À tester, Fait  
4. Ajouter les 15 cartes ci-dessus (la plupart en **Fait** si déjà livrées)  
5. Menu du board → **Partager** → **Créer un lien** / invitation  
6. Coller l’URL dans le README et ce document (déjà fait)  
7. Pas obligatoire de rendre le board **Public** : un lien d’invitation dans le rapport suffit pour le jury
