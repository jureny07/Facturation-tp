<?php
// includes/fonctions-produits.php — Fonctions de gestion du catalogue produits

require_once __DIR__ . '/../config/config.php';

/**
 * Charge tous les produits depuis le fichier JSON.
 */
function charger_produits(): array {
    if (!file_exists(PRODUITS_FILE)) return [];
    $contenu = file_get_contents(PRODUITS_FILE);
    return json_decode($contenu, true) ?? [];
}

/**
 * Sauvegarde le tableau des produits dans le fichier JSON.
 */
function sauvegarder_produits(array $produits): bool {
    return file_put_contents(PRODUITS_FILE, json_encode(array_values($produits), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Recherche un produit par son code-barres.
 */
function trouver_produit(string $code_barre): ?array {
    $produits = charger_produits();
    foreach ($produits as $p) {
        if ($p['code_barre'] === $code_barre) return $p;
    }
    return null;
}

/**
 * Enregistre un nouveau produit ou met à jour un existant.
 * Retourne true si succès, ou un message d'erreur en cas d'échec.
 */
function enregistrer_produit(array $donnees): bool|string {
    // Validation
    $erreurs = valider_produit($donnees);
    if (!empty($erreurs)) return implode(' | ', $erreurs);

    $produits = charger_produits();
    $code     = trim($donnees['code_barre']);

    foreach ($produits as &$p) {
        if ($p['code_barre'] === $code) {
            // Mise à jour
            $p['nom']               = htmlspecialchars(trim($donnees['nom']), ENT_QUOTES);
            $p['prix_unitaire_ht']  = (float) $donnees['prix_unitaire_ht'];
            $p['date_expiration']   = $donnees['date_expiration'];
            $p['quantite_stock']    = (int) $donnees['quantite_stock'];
            return sauvegarder_produits($produits) ? true : "Erreur d'écriture.";
        }
    }
    unset($p);

    // Nouveau produit
    $produits[] = [
        'code_barre'          => $code,
        'nom'                 => htmlspecialchars(trim($donnees['nom']), ENT_QUOTES),
        'prix_unitaire_ht'    => (float) $donnees['prix_unitaire_ht'],
        'date_expiration'     => $donnees['date_expiration'],
        'quantite_stock'      => (int) $donnees['quantite_stock'],
        'date_enregistrement' => date('Y-m-d'),
    ];
    return sauvegarder_produits($produits) ? true : "Erreur d'écriture.";
}

/**
 * Décrémente le stock d'un produit après une vente.
 */
function decrementer_stock(string $code_barre, int $quantite_vendue): bool {
    $produits = charger_produits();
    foreach ($produits as &$p) {
        if ($p['code_barre'] === $code_barre) {
            $p['quantite_stock'] -= $quantite_vendue;
            return sauvegarder_produits($produits);
        }
    }
    return false;
}

/**
 * Valide les données d'un produit. Retourne un tableau d'erreurs (vide si OK).
 */
function valider_produit(array $donnees): array {
    $erreurs = [];
    if (empty(trim($donnees['code_barre'] ?? ''))) $erreurs[] = "Code-barres obligatoire.";
    if (empty(trim($donnees['nom'] ?? '')))         $erreurs[] = "Nom du produit obligatoire.";

    $prix = $donnees['prix_unitaire_ht'] ?? '';
    if (!is_numeric($prix) || (float)$prix <= 0) $erreurs[] = "Le prix doit être un nombre positif.";

    $qte = $donnees['quantite_stock'] ?? '';
    if (!ctype_digit((string)$qte) || (int)$qte < 0) $erreurs[] = "La quantité doit être un entier positif ou nul.";

    $date = $donnees['date_expiration'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate(
        (int)substr($date,5,2), (int)substr($date,8,2), (int)substr($date,0,4)
    )) {
        $erreurs[] = "Date d'expiration invalide (format attendu : AAAA-MM-JJ).";
    }
    return $erreurs;
}
