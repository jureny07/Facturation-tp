<?php
// includes/fonctions-auth.php — Fonctions d'authentification et de gestion des utilisateurs

require_once __DIR__ . '/../config/config.php';

/**
 * Charge tous les utilisateurs depuis le fichier JSON.
 */
function charger_utilisateurs(): array {
    if (!file_exists(USERS_FILE)) return [];
    $contenu = file_get_contents(USERS_FILE);
    return json_decode($contenu, true) ?? [];
}

/**
 * Sauvegarde le tableau des utilisateurs dans le fichier JSON.
 */
function sauvegarder_utilisateurs(array $utilisateurs): bool {
    return file_put_contents(USERS_FILE, json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Recherche un utilisateur par son identifiant.
 */
function trouver_utilisateur(string $identifiant): ?array {
    $utilisateurs = charger_utilisateurs();
    foreach ($utilisateurs as $u) {
        if ($u['identifiant'] === $identifiant) return $u;
    }
    return null;
}

/**
 * Authentifie un utilisateur. Retourne true si succès, false sinon.
 */
function authentifier(string $identifiant, string $mot_de_passe): bool {
    $utilisateur = trouver_utilisateur($identifiant);
    if (!$utilisateur || !$utilisateur['actif']) return false;
    if (!password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) return false;

    $_SESSION['identifiant']  = $utilisateur['identifiant'];
    $_SESSION['role']         = $utilisateur['role'];
    $_SESSION['nom_complet']  = $utilisateur['nom_complet'];
    return true;
}

/**
 * Vérifie si l'utilisateur courant possède au moins le rôle requis.
 */
function a_role(string $role_requis): bool {
    global $ROLES_HIERARCHY;
    if (!isset($_SESSION['role'])) return false;
    $rang_actuel  = $ROLES_HIERARCHY[$_SESSION['role']]  ?? 0;
    $rang_requis  = $ROLES_HIERARCHY[$role_requis]        ?? 99;
    return $rang_actuel >= $rang_requis;
}

/**
 * Redirige vers login si non connecté, ou vers accueil si rôle insuffisant.
 */
function exiger_role(string $role_requis): void {
    if (!isset($_SESSION['identifiant'])) {
        header('Location: /auth/login.php');
        exit;
    }
    if (!a_role($role_requis)) {
        $_SESSION['erreur'] = "Accès refusé : vous n'avez pas les droits nécessaires.";
        header('Location: /index.php');
        exit;
    }
}

/**
 * Ajoute un nouvel utilisateur. Retourne true si succès.
 */
function ajouter_utilisateur(string $identifiant, string $mot_de_passe, string $role, string $nom_complet): bool|string {
    if (trouver_utilisateur($identifiant)) return "Cet identifiant existe déjà.";
    $utilisateurs   = charger_utilisateurs();
    $utilisateurs[] = [
        'identifiant'    => htmlspecialchars($identifiant, ENT_QUOTES),
        'mot_de_passe'   => password_hash($mot_de_passe, PASSWORD_DEFAULT),
        'role'           => $role,
        'nom_complet'    => htmlspecialchars($nom_complet, ENT_QUOTES),
        'date_creation'  => date('Y-m-d'),
        'actif'          => true,
    ];
    return sauvegarder_utilisateurs($utilisateurs) ? true : "Erreur d'écriture du fichier.";
}

/**
 * Supprime un utilisateur par identifiant (interdit de supprimer le dernier superadmin).
 */
function supprimer_utilisateur(string $identifiant): bool|string {
    $utilisateurs = charger_utilisateurs();
    $superadmins  = array_filter($utilisateurs, fn($u) => $u['role'] === ROLE_SUPERADMIN && $u['actif']);
    $cible        = trouver_utilisateur($identifiant);
    if (!$cible) return "Utilisateur introuvable.";
    if ($cible['role'] === ROLE_SUPERADMIN && count($superadmins) <= 1) {
        return "Impossible de supprimer le dernier Super Administrateur.";
    }
    $utilisateurs = array_values(array_filter($utilisateurs, fn($u) => $u['identifiant'] !== $identifiant));
    return sauvegarder_utilisateurs($utilisateurs) ? true : "Erreur d'écriture du fichier.";
}
