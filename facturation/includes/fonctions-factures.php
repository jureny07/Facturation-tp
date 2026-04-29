<?php
// includes/fonctions-factures.php — Fonctions de gestion de la facturation

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions-produits.php';

/**
 * Charge toutes les factures.
 */
function charger_factures(): array {
    if (!file_exists(FACTURES_FILE)) return [];
    return json_decode(file_get_contents(FACTURES_FILE), true) ?? [];
}

/**
 * Sauvegarde les factures dans le fichier JSON.
 */
function sauvegarder_factures(array $factures): bool {
    return file_put_contents(FACTURES_FILE, json_encode(array_values($factures), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Génère un identifiant unique de facture : FAC-AAAAMMJJ-NNN
 */
function generer_id_facture(): string {
    $factures    = charger_factures();
    $date_str    = date('Ymd');
    $count       = 1;
    foreach ($factures as $f) {
        if (strpos($f['id_facture'], "FAC-{$date_str}-") === 0) $count++;
    }
    return sprintf('FAC-%s-%03d', $date_str, $count);
}

/**
 * Calcule les totaux d'un panier d'articles.
 * Chaque article : ['code_barre', 'nom', 'prix_unitaire_ht', 'quantite', 'sous_total_ht']
 */
function calculer_totaux(array $articles): array {
    $total_ht = array_sum(array_column($articles, 'sous_total_ht'));
    $tva      = round($total_ht * TVA_TAUX, 2);
    $total_ttc = round($total_ht + $tva, 2);
    return compact('total_ht', 'tva', 'total_ttc');
}

/**
 * Valide et finalise une facture. Décrémente le stock.
 * Retourne l'identifiant de la facture créée, ou un message d'erreur.
 */
function valider_facture(array $articles, string $caissier): string|array {
    if (empty($articles)) return "La facture ne contient aucun article.";

    // Vérification des stocks avant toute écriture
    foreach ($articles as $art) {
        $produit = trouver_produit($art['code_barre']);
        if (!$produit) return "Produit introuvable : {$art['code_barre']}.";
        if ($art['quantite'] > $produit['quantite_stock']) {
            return "Stock insuffisant pour « {$art['nom']} » (disponible : {$produit['quantite_stock']}).";
        }
    }

    $totaux   = calculer_totaux($articles);
    $facture  = [
        'id_facture' => generer_id_facture(),
        'date'       => date('Y-m-d'),
        'heure'      => date('H:i:s'),
        'caissier'   => $caissier,
        'articles'   => $articles,
        'total_ht'   => $totaux['total_ht'],
        'tva'        => $totaux['tva'],
        'total_ttc'  => $totaux['total_ttc'],
    ];

    $factures   = charger_factures();
    $factures[] = $facture;
    if (!sauvegarder_factures($factures)) return "Erreur lors de la sauvegarde de la facture.";

    // Décrémentation des stocks
    foreach ($articles as $art) {
        decrementer_stock($art['code_barre'], $art['quantite']);
    }

    return $facture;
}

/**
 * Retourne les factures du jour.
 */
function factures_du_jour(): array {
    $today    = date('Y-m-d');
    return array_filter(charger_factures(), fn($f) => $f['date'] === $today);
}

/**
 * Retourne les factures d'un mois donné (YYYY-MM).
 */
function factures_du_mois(string $mois): array {
    return array_filter(charger_factures(), fn($f) => substr($f['date'], 0, 7) === $mois);
}
