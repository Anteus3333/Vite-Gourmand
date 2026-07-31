<?php
// config/turnstile.php — Cloudflare Turnstile (anti-spam formulaires)

$defaults = [
    // false = pas de widget (dev local sans clés). true en prod avec clés.
    'enabled'    => false,
    'site_key'   => '',
    'secret_key' => '',
    // Widget : managed | non-interactive | invisible
    'widget'     => 'managed',
];

$local = __DIR__ . '/turnstile.local.php';
if (is_file($local)) {
    $defaults = array_replace_recursive($defaults, require $local);
}

return $defaults;
