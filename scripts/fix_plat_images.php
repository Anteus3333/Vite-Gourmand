<?php
require __DIR__ . '/../config/database.php';

$db = (new Database())->getConnection();

try {
    $db->exec('ALTER TABLE plat ADD COLUMN image VARCHAR(255) NULL AFTER photo');
    echo "Colonne plat.image ajoutée.\n";
} catch (Throwable $e) {
    echo "plat.image : " . $e->getMessage() . "\n";
}

for ($i = 1; $i <= 15; $i++) {
    $path = 'plats/plat-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . '.svg';
    $db->prepare('UPDATE plat SET image = :img WHERE plat_id = :id')
        ->execute([':img' => $path, ':id' => $i]);
}
echo "Photos plats mises à jour.\n";

$count = $db->query('SELECT COUNT(*) FROM menu_image')->fetchColumn();
echo "menu_image : {$count} lignes.\n";
