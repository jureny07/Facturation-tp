<?php
// modules/admin/supprimer-compte.php — Suppression d'un compte utilisateur

require_once __DIR__ . '/../../auth/session.php';
exiger_role(ROLE_SUPERADMIN);
require_once __DIR__ . '/../../includes/fonctions-auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /modules/admin/gestion-comptes.php');
    exit;
}

$identifiant = trim($_POST['identifiant'] ?? '');

if (empty($identifiant)) {
    $_SESSION['erreur'] = "Identifiant manquant.";
} else {
    $resultat = supprimer_utilisateur($identifiant);
    if ($resultat === true) {
        $_SESSION['succes'] = "Compte « {$identifiant} » supprimé.";
    } else {
        $_SESSION['erreur'] = $resultat;
    }
}

header('Location: /facturation/modules/admin/gestion-comptes.php');
exit;
