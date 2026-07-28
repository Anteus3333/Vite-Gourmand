# Organigramme des parcours — Vite et Gourmand

Cartographie des parcours utilisateur du site, basée sur `src/Router.php` et les contrôleurs.

## Rôles et accès

| Rôle | Session | Espace principal | Accès restreint |
|------|---------|------------------|-----------------|
| **Visiteur** | Non connecté | Pages publiques | Commande, mon compte |
| **Utilisateur** (client) | `role = utilisateur` | `/mon-compte` | Espace employé, admin |
| **Employé** | `role = employe` | `/espace-employe` | Admin exclusif |
| **Administrateur** | `role = administrateur` | `/admin` | Tout (employé + gestion employés + stats) |

> L'administrateur accède aussi aux fonctionnalités employé (menus, commandes, avis, horaires).

---

## Vue d'ensemble du site

```mermaid
flowchart TB
    subgraph PUBLIC["🌐 Public (sans connexion)"]
        ACC["/ ou /accueil"]
        MENUS["/menus"]
        MENU["/menu/{id}"]
        CONTACT["/contact"]
        LEGAL["Pages légales"]
        AUTH["Authentification"]
    end

    subgraph CLIENT["👤 Client connecté"]
        COMPTE["/mon-compte"]
        CMD["/commande"]
    end

    subgraph EMPLOYE["👔 Employé / Admin"]
        ESP["/espace-employe"]
    end

    subgraph ADMIN["⚙️ Administrateur"]
        ADM["/admin"]
    end

    ACC --> MENUS --> MENU
    MENU -->|"Commander"| CMD
    MENUS --> CMD
    AUTH -->|"login OK"| COMPTE
    AUTH -->|"login OK"| ESP
    AUTH -->|"login OK admin"| ADM
    COMPTE --> CMD
```

---

## Parcours visiteur (public)

```mermaid
flowchart LR
    START((Arrivée)) --> ACC["Accueil<br/>/"]
    ACC --> MENUS["Catalogue menus<br/>/menus"]
    ACC --> CONTACT["Contact<br/>/contact"]
    ACC --> LEGAL["Mentions, CGV,<br/>Accessibilité, RGPD"]

    MENUS -->|"filtre AJAX"| API1["/api/filter-menus"]
    MENUS --> DETAIL["Détail menu<br/>/menu/{id}"]
    DETAIL -->|"Bouton Commander"| LOGIN1["/login?redirect=/commande"]

    CONTACT -->|"formulaire POST"| CONTACT
    LEGAL --> ACC

    ACC --> LOGIN["Connexion<br/>/login"]
    ACC --> INSC["Inscription<br/>/inscription"]
    LOGIN --> MDP["Mot de passe oublié<br/>/mot-de-passe-oublie"]
    MDP --> RESET["Réinitialisation<br/>/reinitialisation?token="]
    INSC --> CONF["Confirmation email<br/>/confirmation-email?token="]
```

---

## Parcours authentification

```mermaid
flowchart TD
    subgraph INSCRIPTION
        I1["/inscription"] --> I2{"Validation<br/>formulaire"}
        I2 -->|OK| I3["Compte créé<br/>email confirmation"]
        I2 -->|Erreur| I1
        I3 --> I4["/confirmation-email"]
        I4 --> I5["Compte activé → /login"]
    end

    subgraph CONNEXION
        L1["/login"] --> L2{"Identifiants<br/>+ anti-bruteforce"}
        L2 -->|OK| L3{"Rôle ?"}
        L2 -->|KO| L1
        L3 -->|utilisateur| MC["/mon-compte ou redirect"]
        L3 -->|employe| EE["/espace-employe"]
        L3 -->|administrateur| AD["/admin"]
    end

    subgraph MDP
        M1["/mot-de-passe-oublie"] --> M2["Email lien reset"]
        M2 --> M3["/reinitialisation"]
        M3 --> M4["/login"]
    end

    DECO["/deconnexion POST"] --> ACC["/"]
```

---

## Parcours client — commande et compte

```mermaid
flowchart TD
    subgraph COMMANDE["Passer commande"]
        C0{"Connecté ?"}
        C0 -->|Non| CLOGIN["/login?redirect=/commande"]
        C0 -->|Oui| C1["/commande"]
        C1 --> C2["Choix menu, date, nb personnes,<br/>adresse livraison"]
        C2 --> API2["/api/calcul-prix"]
        C2 --> API3["/api/calcul-distance"]
        C2 --> C3{"Validation + CGV"}
        C3 -->|OK| C4["Commande enregistrée<br/>statut: en attente"]
        C3 -->|KO| C1
        C4 --> C5["Email confirmation"]
        C4 --> MC["/mon-compte/commandes"]
    end

    subgraph MON_COMPTE["Mon compte /mon-compte"]
        MC0["Accueil compte"] --> MC1["Mes commandes"]
        MC0 --> MC2["Mon profil"]
        MC0 --> MC3["Changer mot de passe"]
        MC0 --> MC4["Mes avis"]
        MC0 --> MC5["Supprimer compte"]

        MC1 --> D1["Détail commande<br/>/mon-compte/commande/{n°}"]
        D1 --> MOD["Modifier<br/>/.../modifier"]
        D1 --> ANN["Annuler<br/>/.../annuler"]
        D1 --> AVIS["Laisser un avis<br/>/.../avis"]
        AVIS --> MC4
    end

    CLOGIN --> C1
```

**Règles métier commande client :**
- Modification / annulation selon statut et délai (config `commande.php`)
- Avis possible après prestation livrée

---

## Parcours employé — `/espace-employe`

Accessible aux rôles **employé** et **administrateur**.

```mermaid
flowchart TD
    EE["/espace-employe"] --> EE0["Tableau de bord"]
    EE0 --> EEC["Commandes<br/>/espace-employe/commandes"]
    EE0 --> EEM["Menus<br/>/espace-employe/menus"]
    EE0 --> EEH["Horaires<br/>/espace-employe/horaires"]
    EE0 --> EEA["Modération avis<br/>/espace-employe/avis"]

    EEC --> ECD["Détail commande<br/>/commande/{n°}"]
    ECD --> ECS["Changer statut<br/>/.../statut"]
    ECD --> ECA["Annuler<br/>/.../annuler"]
    ECD --> ECM["Matériel<br/>/.../materiel"]

    EEM --> EMC["Créer menu<br/>/menu/nouveau"]
    EEM --> EMM["Modifier<br/>/menu/{id}/modifier"]
    EEM --> EMP["Gérer plats<br/>/menu/{id}/plats"]
    EEM --> EMV["Visibilité<br/>/menu/{id}/visibilite"]
    EEM --> EMS["Supprimer<br/>/menu/{id}/supprimer"]

    EEA --> EAV["Valider avis<br/>/avis/{id}/valider"]
    EEA --> EAR["Refuser avis<br/>/avis/{id}/refuser"]
```

**Catalogue (employé) :**
- Création menu : photo obligatoire, masqué par défaut (`visible = 0`)
- Visibilité : minimum 3 plats avec photo
- Galerie auto : couverture + 3 premiers plats photo

---

## Parcours administrateur — `/admin`

Réservé au rôle **administrateur**. Reprend l'espace employé + fonctions exclusives.

```mermaid
flowchart TD
    AD["/admin"] --> AD0["Dashboard + statistiques"]
    AD0 --> ADS["Sync Mongo<br/>/admin/stats/sync-mongo"]
    AD0 --> ADC["Commandes"]
    AD0 --> ADM["Menus"]
    AD0 --> ADH["Horaires"]
    AD0 --> ADA["Avis"]
    AD0 --> ADE["Employés<br/>/admin/employes"]

    ADC --> ADCD["Détail / statut / annuler / matériel<br/>(identique employé)"]
    ADM --> ADMG["CRUD menus + plats<br/>(identique employé)"]
    ADA --> ADAV["Valider / refuser avis"]

    ADE --> ADEN["Nouvel employé<br/>/admin/employe/nouveau"]
    ADE --> ADEM["Modifier<br/>/admin/employe/{id}/modifier"]
    ADE --> ADEA["Activer / Désactiver"]
    ADE --> ADES["Supprimer"]
```

**Stats admin :**
- Source MongoDB Atlas si disponible, sinon MySQL
- Bouton synchronisation MySQL → Mongo (ECF)

---

## APIs internes (AJAX)

| Route | Usage |
|-------|--------|
| `GET /api/filter-menus` | Filtrage catalogue (régime, prix, personnes…) |
| `POST /api/calcul-prix` | Calcul tarif commande |
| `POST /api/calcul-distance` | Distance livraison depuis Bordeaux |

---

## Pages légales (footer)

- `/mentions-legales`
- `/cgv`
- `/accessibilite`
- `/politique-confidentialite`

---

## Schéma des redirections de sécurité

```mermaid
flowchart LR
    P["Page protégée"] --> G{"AuthGuard"}
    G -->|Non connecté| L["/login?redirect=..."]
    G -->|Mauvais rôle| H["/ accueil<br/>flash erreur"]
    G -->|OK| P
```

| Zone | Garde |
|------|-------|
| `/commande`, `/mon-compte/*` | Connexion client |
| `/espace-employe/*` | Rôle employé ou admin |
| `/admin/*` | Rôle administrateur uniquement |

---

*Généré à partir du code source — juillet 2026*
