<?php
// config/commandeconfig.php — règles métier de la commande (ECF)

return [
    // Livraison : 5 € + 0,59 €/km hors Bordeaux
    'livraison_base'          => 5.00,
    'livraison_par_km'        => 0.59,
    'ville_livraison_gratuite'=> 'bordeaux',
    // Point de départ pour le calcul auto de distance (centre de Bordeaux)
    'bordeaux_lat'            => 44.8378,
    'bordeaux_lon'            => -0.5792,

    // Réduction de 10 % si nb personnes >= minimum du menu + 5
    'reduction_seuil_personnes'=> 5,
    'reduction_pourcent'      => 10,

    // Matériel prêté — rappel CGV (ECF)
    'materiel_delai_jours_ouvres'      => 10,
    'materiel_frais_non_restitution'   => 600,

    // Modes de contact avant annulation par l'employé (ECF)
    'modes_contact_annulation' => [
        'email'     => 'E-mail',
        'telephone' => 'Téléphone',
        'courrier'  => 'Courrier',
        'sur_place' => 'Sur place',
    ],

    // Statut initial à la création
    'statut_initial'          => 'en_attente',

    // Statuts autorisant modification / annulation par l'utilisateur (ECF)
    'statuts_modifiables'     => ['en_attente'],
    'statuts_annulables'      => ['en_attente'],

    // Transitions autorisées par l'employé (clés normalisées sans accent)
    'workflow_employe' => [
        'en_attente'       => ['acceptee', 'annulee'],
        'confirmee'        => ['en_preparation', 'annulee'],
        'acceptee'         => ['en_preparation'],
        'en_preparation'   => ['en_livraison'],
        'en_livraison'     => ['livree'],
        'livree'           => ['attente_materiel', 'terminee'],
        'attente_materiel' => ['terminee'],
        'terminee'         => [],
        'annulee'          => [],
    ],

    // Libellés affichés dans l'espace utilisateur
    'libelles_statut' => [
        'en_attente'        => 'En attente de validation',
        'acceptee'          => 'Acceptée',
        'en_preparation'    => 'En préparation',
        'en_livraison'      => 'En cours de livraison',
        'livree'            => 'Livrée',
        'attente_materiel'  => 'En attente du retour de matériel',
        'terminee'          => 'Terminée',
        'annulee'           => 'Annulée',
        'confirmee'         => 'Confirmée',
    ],
];
