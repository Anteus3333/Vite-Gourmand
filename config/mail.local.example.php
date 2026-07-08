<?php
// Copiez ce fichier en "mail.local.php" et renseignez vos identifiants SMTP.
// mail.local.php n'est pas versionné : vos mots de passe restent privés.
//
// Gmail : créez un "mot de passe d'application" (compte Google > Sécurité)

return [
    'smtp' => [
        'enabled'  => true,
        'host'     => 'smtp.gmail.com',
        'username' => 'vitegourmand322@gmail.com',
        'password' => 'VOTRE_MOT_DE_PASSE_APPLICATION',
    ],
];
