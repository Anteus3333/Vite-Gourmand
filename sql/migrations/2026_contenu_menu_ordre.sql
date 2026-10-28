-- Ajoute l'ordre des plats dans un menu (entrée / plat / dessert).
-- À exécuter une fois sur une base déjà créée (Docker, Laragon, Infomaniak).

ALTER TABLE contenu_menu
  ADD COLUMN ordre INT NOT NULL DEFAULT 1 AFTER plat_id;

UPDATE contenu_menu cm
JOIN (
  SELECT menu_id, plat_id,
         ROW_NUMBER() OVER (PARTITION BY menu_id ORDER BY plat_id) AS rn
  FROM contenu_menu
) x ON x.menu_id = cm.menu_id AND x.plat_id = cm.plat_id
SET cm.ordre = x.rn;
