<?php
require __DIR__ . '/../config/database.php';

$file = $argv[1] ?? '';
if ($file === '' || !is_file($file)) {
    fwrite(STDERR, "Usage: php run_migration.php <fichier.sql>\n");
    exit(1);
}

$db = (new Database())->getConnection();
$sql = file_get_contents($file);

foreach (preg_split('/;\s*\R/', $sql) as $query) {
    $query = trim($query);
    if ($query === '' || preg_match('/^USE\s+/i', $query)) {
        continue;
    }
    try {
        $db->exec($query);
        echo "OK: " . substr(str_replace("\n", ' ', $query), 0, 70) . "...\n";
    } catch (Throwable $e) {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}
