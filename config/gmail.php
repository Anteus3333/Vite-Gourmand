<?php
// config/gmail.php — configuration de l'envoi de mails (SMTP Gmail)
// Surcharge locale : créer gmail.local.php (non versionné).

return [
    // Adresse officielle du traiteur (contact, expéditeur des e-mails automatiques)
    'contact_email' => 'vitegourmand322@gmail.com',

    'from_email' => 'vitegourmand322@gmail.com',
    'from_name'  => 'Vite & Gourmand',

    // SMTP Gmail — activé via config/gmail.local.php
    'smtp' => [
        'enabled'     => false,
        'host'        => 'smtp.gmail.com',
        'port'        => 587,
        'encryption'  => 'tls',
        'username'    => '',    // ex: vitegourmand322@gmail.com
        'password'    => '',    // mot de passe d'application Gmail
    ],
];
