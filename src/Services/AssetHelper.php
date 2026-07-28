<?php
// src/Services/AssetHelper.php — chemins vers les images publiques

class AssetHelper {
    private static function cheminAbsolu(string $relatif): string {
        return __DIR__ . '/../../public/images/' . ltrim($relatif, '/');
    }

    /** URL publique d'une image avec repli sur un visuel par défaut */
    public static function imageUrl(?string $cheminRelatif, string $defaut = 'menus/default.svg'): string {
        $rel = trim($cheminRelatif ?? '');
        if ($rel !== '' && is_file(self::cheminAbsolu($rel))) {
            $parts = array_map('rawurlencode', explode('/', str_replace('\\', '/', $rel)));
            return BASE_URL . '/images/' . implode('/', $parts);
        }
        return BASE_URL . '/images/' . ltrim($defaut, '/');
    }
}
