<?php
// modules/produits/lire.php — API JSON : recherche d'un produit par code-barres

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_CAISSIER);
require_once __DIR__ . '/../../includes/fonctions-produits.php';

header('Content-Type: application/json; charset=utf-8');

$code    = trim($_GET['code'] ?? '');
$produit = $code ? trouver_produit($code) : null;

echo json_encode([
    'found'   => $produit !== null,
    'produit' => $produit,
], JSON_UNESCAPED_UNICODE);
