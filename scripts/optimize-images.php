<?php
/**
 * Optimise les JPEG du site (redimensionnement + compression).
 * Usage : php scripts/optimize-images.php
 */

declare(strict_types=1);

$racine = dirname(__DIR__);
$imagesDir = $racine . '/public/images';

/** @return array<string, array{maxW:int, maxH:int, quality:int}> */
function reglesOptimisation(): array {
    return [
        'plats'           => ['maxW' => 1200, 'maxH' => 900,  'quality' => 80],
        'menus'           => ['maxW' => 800,  'maxH' => 500,  'quality' => 80],
        'banner-accueil.jpg' => ['maxW' => 1600, 'maxH' => 600,  'quality' => 80],
        'julie-josee.jpg'    => ['maxW' => 1120, 'maxH' => 806,  'quality' => 80],
    ];
}

function formatKo(int $octets): string {
    return number_format($octets / 1024, 1, ',', ' ') . ' Ko';
}

function optimiserJpeg(string $chemin, int $maxW, int $maxH, int $quality): array {
    $avant = filesize($chemin) ?: 0;
    $info = @getimagesize($chemin);
    if ($info === false || ($info['mime'] ?? '') !== 'image/jpeg') {
        throw new RuntimeException('JPEG invalide : ' . $chemin);
    }

    [$w, $h] = [$info[0], $info[1]];
    $ratio = min($maxW / $w, $maxH / $h, 1.0);
    $newW = max(1, (int) round($w * $ratio));
    $newH = max(1, (int) round($h * $ratio));

    $src = @imagecreatefromjpeg($chemin);
    if ($src === false) {
        throw new RuntimeException('Lecture impossible : ' . $chemin);
    }

    $dst = imagecreatetruecolor($newW, $newH);
    if ($dst === false) {
        imagedestroy($src);
        throw new RuntimeException('Création image impossible : ' . $chemin);
    }

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
    imagedestroy($src);

    if (!imagejpeg($dst, $chemin, $quality)) {
        imagedestroy($dst);
        throw new RuntimeException('Écriture impossible : ' . $chemin);
    }
    imagedestroy($dst);

    clearstatcache(true, $chemin);
    $apres = filesize($chemin) ?: 0;

    return [
        'avant'      => $avant,
        'apres'      => $apres,
        'dimensions' => $w . 'x' . $h . ' → ' . $newW . 'x' . $newH,
        'gain'       => $avant > 0 ? round((1 - $apres / $avant) * 100, 1) : 0.0,
    ];
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "Extension GD requise.\n");
    exit(1);
}

$regles = reglesOptimisation();
$resultats = [];
$totalAvant = 0;
$totalApres = 0;

foreach ($regles as $cle => $regle) {
    if (str_contains($cle, '.jpg')) {
        $fichiers = [ $imagesDir . '/' . $cle ];
    } else {
        $dossier = $imagesDir . '/' . $cle;
        if (!is_dir($dossier)) {
            continue;
        }
        $fichiers = glob($dossier . '/*.jpg') ?: [];
        sort($fichiers);
    }

    foreach ($fichiers as $chemin) {
        if (!is_file($chemin)) {
            continue;
        }
        $relatif = str_replace('\\', '/', substr($chemin, strlen($racine) + 1));
        $stats = optimiserJpeg($chemin, $regle['maxW'], $regle['maxH'], $regle['quality']);
        $stats['fichier'] = $relatif;
        $resultats[] = $stats;
        $totalAvant += $stats['avant'];
        $totalApres += $stats['apres'];
    }
}

echo "Optimisation terminée (" . count($resultats) . " fichiers)\n\n";
foreach ($resultats as $r) {
    echo sprintf(
        "- %s\n  %s | %s → %s | -%s%%\n",
        $r['fichier'],
        $r['dimensions'],
        formatKo($r['avant']),
        formatKo($r['apres']),
        number_format($r['gain'], 1, ',', ' ')
    );
}

echo "\nTotal : " . formatKo($totalAvant) . " → " . formatKo($totalApres);
echo " (-" . number_format($totalAvant > 0 ? (1 - $totalApres / $totalAvant) * 100 : 0, 1, ',', ' ') . "%)\n";
