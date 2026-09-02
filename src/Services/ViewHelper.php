<?php
// src/Services/ViewHelper.php — composants d'affichage réutilisables


// est une boîte à outils d’affichage pour les vues : 
// des petites fonctions réutilisables qui génèrent du HTML 
// ou formatent du texte, pour ne pas recopier la même logique partout.

// 3 Méthodes pour afficher les noms proprement
// (Jean-Pierre DUPONT)
// formatPrenom() : prénom en minuscule, avec une valeur par défaut.
// formatNom() : nom en majuscule, avec une valeur par défaut.
// formatNomComplet() : nom complet en majuscule, avec une valeur par défaut.

// btnNav() : Bouton de navigation (sans lien <a> classique)

// champHeure() : champ heure custom (2 listes custom + hidden HH:MM) — pas de select natif (bleu).
// champDate() : champ date custom (bouton + calendrier + hidden YYYY-MM-DD).

class ViewHelper {

    /** Prénom : première lettre de chaque mot en majuscule (ex. Jean-Pierre). */
    public static function formatPrenom(?string $prenom): string {
        $prenom = trim((string) $prenom);
        if ($prenom === '') {
            return '';
        }
        // mb_convert_case() convertit la première lettre de chaque mot en majuscule
        // mb_strtolower() convertit le prénom en minuscule
        // MB_CASE_TITLE convertit la première lettre de chaque mot en majuscule
        // UTF-8 est le jeu de caractères utilisé
        return mb_convert_case(mb_strtolower($prenom, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    /** Nom de famille : entièrement en majuscules. */
    public static function formatNom(?string $nom): string {
        $nom = trim((string) $nom);
        if ($nom === '') {
            return '';
        }
        // mb_strtoupper() convertit le nom en majuscule
        // UTF-8 est le jeu de caractères utilisé
        return mb_strtoupper($nom, 'UTF-8');
    }

    /** Affichage standard « Prénom NOM » (indépendant de la casse saisie). */
    // fallback est une valeur par défaut si le prénom ou le nom est vide
    public static function formatNomComplet(?string $prenom, ?string $nom, string $fallback = ''): string {
        $complet = trim(self::formatPrenom($prenom) . ' ' . self::formatNom($nom));
        // trim() retire les espaces en début et fin de la chaîne
        // self::formatPrenom($prenom) appelle la méthode formatPrenom() de la classe ViewHelper
        // self::formatNom($nom) appelle la méthode formatNom() de la classe ViewHelper
        // $complet est le nom complet
        // $complet !== '' vérifie si le nom complet n'est pas vide
        // $complet !== '' ? $complet : $fallback retourne le nom complet si il n'est pas vide, sinon la valeur par défaut
        return $complet !== '' ? $complet : $fallback;
    }

    /**
     * Bouton de navigation sans prévisualisation d'URL au survol (contrairement à <a href>).
     */
    // url est l'URL de la page
    // label est le texte du bouton
    // classes est la classe CSS du bouton
    // attrs est un tableau d'attributs HTML
    public static function btnNav(string $url, string $label, string $classes = 'btn', array $attrs = []): string {
        $action = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        // htmlspecialchars() convertit les caractères spéciaux en entités HTML
        // ENT_QUOTES convertit les quotes en entités HTML
        // UTF-8 est le jeu de caractères utilisé
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

    /**
     * Champ heure custom (2 listes custom + hidden HH:MM) — pas de select natif (bleu).
     */
    public static function champHeure(
        string $id,
        string $name,
        string $value = '',
        bool $required = true,
        int $stepMinutes = 15
    ): string {
        $value = substr(trim($value), 0, 5);
        if ($value !== '' && !preg_match('/^\d{2}:\d{2}$/', $value)) {
            $value = '';
        }
        $heure = $value !== '' ? (int) substr($value, 0, 2) : -1;
        $minute = $value !== '' ? (int) substr($value, 3, 2) : -1;
        if ($stepMinutes < 1) {
            $stepMinutes = 15;
        }
        if ($minute >= 0 && ($minute % $stepMinutes) !== 0) {
            $minute = (int) (round($minute / $stepMinutes) * $stepMinutes);
            if ($minute >= 60) {
                $minute = 60 - $stepMinutes;
            }
            $value = sprintf('%02d:%02d', $heure, $minute);
        }

        $reqAttr = $required ? ' required' : '';
        $idEsc = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
        $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $valEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $heureLabel = $heure >= 0 ? sprintf('%02d', $heure) : '--';
        $minuteLabel = $minute >= 0 ? sprintf('%02d', $minute) : '--';
        $heurePh = $heure < 0 ? ' time-picker-placeholder' : '';
        $minutePh = $minute < 0 ? ' time-picker-placeholder' : '';

        $heuresJson = [];
        for ($h = 0; $h <= 23; $h++) {
            $heuresJson[] = sprintf('%02d', $h);
        }
        $minutesJson = [];
        for ($m = 0; $m < 60; $m += $stepMinutes) {
            $minutesJson[] = sprintf('%02d', $m);
        }

        $html = '<div class="time-picker" data-time-picker data-step="' . (int) $stepMinutes . '"'
            . ' data-hours="' . htmlspecialchars(json_encode($heuresJson), ENT_QUOTES, 'UTF-8') . '"'
            . ' data-minutes="' . htmlspecialchars(json_encode($minutesJson), ENT_QUOTES, 'UTF-8') . '"'
            . ($required ? ' data-required="1"' : '')
            . '>';
        $html .= '<input type="hidden" name="' . $nameEsc . '" value="' . $valEsc . '" data-time-value' . $reqAttr . '>';
        $html .= '<div class="time-picker-selects">';

        $html .= '<div class="time-picker-unit" data-time-unit="hour">';
        $html .= '<button type="button" class="time-picker-trigger form-select" id="' . $idEsc . '"'
            . ' aria-haspopup="listbox" aria-expanded="false" aria-label="Heures">';
        $html .= '<span class="time-picker-label' . $heurePh . '" data-time-label>' . htmlspecialchars($heureLabel) . '</span>';
        $html .= '</button>';
        $html .= '<div class="time-picker-panel" hidden role="listbox" aria-label="Heures"></div>';
        $html .= '</div>';

        $html .= '<span class="time-picker-sep" aria-hidden="true">:</span>';

        $html .= '<div class="time-picker-unit" data-time-unit="minute">';
        $html .= '<button type="button" class="time-picker-trigger form-select" id="' . $idEsc . '-m"'
            . ' aria-haspopup="listbox" aria-expanded="false" aria-label="Minutes">';
        $html .= '<span class="time-picker-label' . $minutePh . '" data-time-label>' . htmlspecialchars($minuteLabel) . '</span>';
        $html .= '</button>';
        $html .= '<div class="time-picker-panel" hidden role="listbox" aria-label="Minutes"></div>';
        $html .= '</div>';

        $html .= '</div></div>';
        return $html;
    }

    /**
     * Champ date custom (bouton + calendrier + hidden YYYY-MM-DD).
     *
     * @param string|null $min Date minimale YYYY-MM-DD
     * @param string|null $max Date maximale YYYY-MM-DD
     */
    public static function champDate(
        string $id,
        string $name,
        string $value = '',
        bool $required = true,
        ?string $min = null,
        ?string $max = null
    ): string {
        $value = trim($value);
        if ($value !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $value = '';
        }
        $display = '';
        if ($value !== '') {
            $ts = strtotime($value);
            $display = $ts ? date('d/m/Y', $ts) : '';
        }

        $reqAttr = $required ? ' required' : '';
        $idEsc = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
        $nameEsc = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $valEsc = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $dispEsc = htmlspecialchars($display, ENT_QUOTES, 'UTF-8');
        $minEsc = $min !== null ? htmlspecialchars($min, ENT_QUOTES, 'UTF-8') : '';
        $maxEsc = $max !== null ? htmlspecialchars($max, ENT_QUOTES, 'UTF-8') : '';

        $html = '<div class="date-picker" data-date-picker'
            . ($minEsc !== '' ? ' data-min="' . $minEsc . '"' : '')
            . ($maxEsc !== '' ? ' data-max="' . $maxEsc . '"' : '')
            . '>';
        $html .= '<input type="hidden" name="' . $nameEsc . '" value="' . $valEsc . '" data-date-value' . $reqAttr . '>';
        $html .= '<button type="button" class="date-picker-trigger form-select" id="' . $idEsc . '"'
            . ' aria-haspopup="dialog" aria-expanded="false"'
            . ($required ? ' aria-required="true"' : '')
            . '>';
        $html .= $dispEsc !== ''
            ? '<span class="date-picker-label">' . $dispEsc . '</span>'
            : '<span class="date-picker-label date-picker-placeholder">JJ/MM/AAAA</span>';
        $html .= '</button>';
        $html .= '<div class="date-picker-panel" hidden role="dialog" aria-label="Choisir une date"></div>';
        $html .= '</div>';
        return $html;
    }
}
