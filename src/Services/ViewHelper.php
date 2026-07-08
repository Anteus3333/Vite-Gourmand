<?php
// src/Services/ViewHelper.php — composants d'affichage réutilisables

class ViewHelper {

    /**
     * Bouton de navigation sans prévisualisation d'URL au survol (contrairement à <a href>).
     */
    public static function btnNav(string $url, string $label, string $classes = 'btn', array $attrs = []): string {
        $action = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $labelEsc = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $classesEsc = htmlspecialchars($classes, ENT_QUOTES, 'UTF-8');

        $extra = '';
        foreach ($attrs as $name => $value) {
            $extra .= ' ' . htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return '<button type="button" class="' . $classesEsc . '" data-navigate="' . $action . '"' . $extra . '>'
            . $labelEsc
            . '</button>';
    }
}
