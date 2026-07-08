<?php
// config/mail.php — configuration générale de l'envoi de mails

return [
    // Adresse officielle du traiteur (contact, expéditeur des e-mails automatiques)
    'contact_email' => 'vitegourmand322@gmail.com',

    'from_email' => 'vitegourmand322@gmail.com',
    'from_name'  => 'Vite & Gourmand',

    // SMTP Gmail — activé via config/mail.local.php
    'smtp' => [
        'enabled'     => false,
        'host'        => 'smtp.gmail.com',
        'port'        => 587,
        'encryption'  => 'tls',
        'username'    => '',    // ex: vitegourmand322@gmail.com
        'password'    => '',    // mot de passe d'application Gmail
    ],
];
