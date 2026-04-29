<?php
// config/config.php — Paramètres globaux de l'application

define('APP_NAME', 'SuperMarché — Système de Caisse');
define('TVA_TAUX', 0.18); // 18%
define('CURRENCY', 'CDF');

// Chemins des fichiers de données
define('DATA_DIR',      __DIR__ . '/../data/');
define('PRODUITS_FILE', DATA_DIR . 'produits.json');
define('FACTURES_FILE', DATA_DIR . 'factures.json');
define('USERS_FILE',    DATA_DIR . 'utilisateurs.json');

// Rôles disponibles
define('ROLE_CAISSIER',    'caissier');
define('ROLE_MANAGER',     'manager');
define('ROLE_SUPERADMIN',  'superadmin');

// Hiérarchie des rôles (plus la valeur est haute, plus le rôle est puissant)
$ROLES_HIERARCHY = [
    ROLE_CAISSIER   => 1,
    ROLE_MANAGER    => 2,
    ROLE_SUPERADMIN => 3,
];
