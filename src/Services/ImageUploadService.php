<?php
// src/Services/ImageUploadService.php — upload d'images sécurisé (menus / plats)

class ImageUploadService {
    private const MAX_OCTETS = 5_242_880; // 5 Mo

    /** MIME autorisés → extension canonique */
    private const MIME_AUTORISES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** @return array{ok:bool, relatif:?string, erreur:?string} */
    public function telechargerCouvertureMenu(?array $fichier, bool $obligatoire = false): array {
        return $this->telecharger($fichier, 'menus', $obligatoire);
    }

    /** @return array{ok:bool, relatif:?string, erreur:?string} */
    public function telechargerPhotoPlat(?array $fichier, bool $obligatoire = false): array {
        return $this->telecharger($fichier, 'plats', $obligatoire);
    }

    /**
     * @return array{ok:bool, relatif:?string, erreur:?string}
     */
    public function telecharger(?array $fichier, string $sousDossier, bool $obligatoire = false): array {
        $sousDossier = trim($sousDossier, '/');
        if (!in_array($sousDossier, ['menus', 'plats'], true)) {
            return ['ok' => false, 'relatif' => null, 'erreur' => 'Dossier image non autorisé.'];
        }

        if ($fichier === null || ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($obligatoire) {
                return ['ok' => false, 'relatif' => null, 'erreur' => 'La photo est obligatoire.'];
            }
            return ['ok' => true, 'relatif' => null, 'erreur' => null];
        }

        $code = (int) ($fichier['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($code !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'relatif' => null, 'erreur' => $this->messageErreurUpload($code)];
        }

        $tmp = (string) ($fichier['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'relatif' => null, 'erreur' => 'Fichier upload invalide.'];
        }

        $taille = (int) ($fichier['size'] ?? 0);
        if ($taille <= 0 || $taille > self::MAX_OCTETS) {
            return ['ok' => false, 'relatif' => null, 'erreur' => 'La photo doit peser au maximum 5 Mo.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp) ?: '';
        if (!isset(self::MIME_AUTORISES[$mime])) {
            return ['ok' => false, 'relatif' => null, 'erreur' => 'Format non autorisé (JPEG, PNG ou WebP uniquement).'];
        }

        $info = @getimagesize($tmp);
        if ($info === false || empty($info['mime']) || !isset(self::MIME_AUTORISES[$info['mime']])) {
            return ['ok' => false, 'relatif' => null, 'erreur' => 'Le fichier n’est pas une image valide.'];
        }
        if ($info['mime'] !== $mime) {
            return ['ok' => false, 'relatif' => null, 'erreur' => 'Type d’image incohérent.'];
        }

        $ext = self::MIME_AUTORISES[$mime];
        $nom = 'upload-' . bin2hex(random_bytes(16)) . '.' . $ext;
        $dir = $this->racineImages() . DIRECTORY_SEPARATOR . $sousDossier;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['ok' => false, 'relatif' => null, 'erreur' => 'Impossible de créer le dossier images.'];
        }

        $chemin = $dir . DIRECTORY_SEPARATOR . $nom;
        if (!$this->ecrireImageSecurisee($tmp, $chemin, $mime)) {
            return ['ok' => false, 'relatif' => null, 'erreur' => 'Échec de l’enregistrement de la photo.'];
        }

        @chmod($chemin, 0644);

        return [
            'ok'      => true,
            'relatif' => $sousDossier . '/' . $nom,
            'erreur'  => null,
        ];
    }

    /** Supprime un fichier uploadé (préfixe upload-) sous public/images/. */
    public function supprimerSiUpload(?string $relatif): void {
        $relatif = trim((string) $relatif);
        if ($relatif === '' || !str_starts_with(basename($relatif), 'upload-')) {
            return;
        }
        if (str_contains($relatif, '..') || str_contains($relatif, "\0")) {
            return;
        }
        $abs = $this->racineImages() . '/' . ltrim(str_replace('\\', '/', $relatif), '/');
        $realBase = realpath($this->racineImages());
        $realFile = realpath($abs);
        if ($realBase && $realFile && str_starts_with($realFile, $realBase) && is_file($realFile)) {
            @unlink($realFile);
        }
    }

    private function ecrireImageSecurisee(string $tmp, string $dest, string $mime): bool {
        if (extension_loaded('gd')) {
            $image = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($tmp),
                'image/png'  => @imagecreatefrompng($tmp),
                'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
                default      => false,
            };
            if ($image === false) {
                return false;
            }
            $ok = match ($mime) {
                'image/jpeg' => imagejpeg($image, $dest, 85),
                'image/png'  => imagepng($image, $dest, 6),
                'image/webp' => function_exists('imagewebp') && imagewebp($image, $dest, 85),
                default      => false,
            };
            imagedestroy($image);
            return (bool) $ok;
        }

        return move_uploaded_file($tmp, $dest);
    }

    private function racineImages(): string {
        return dirname(__DIR__, 2) . '/public/images';
    }

    private function messageErreurUpload(int $code): string {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La photo dépasse la taille maximale autorisée.',
            UPLOAD_ERR_PARTIAL => 'Le transfert de la photo a été interrompu.',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire serveur indisponible.',
            UPLOAD_ERR_CANT_WRITE => 'Écriture disque impossible.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqué par une extension PHP.',
            default => 'Erreur lors de l’envoi de la photo.',
        };
    }
}
