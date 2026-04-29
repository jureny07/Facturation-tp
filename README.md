# Facturation-tp
======================================================
  SuperCaisse — Système de Facturation avec Codes-Barres
  Université Protestante au Congo — FASI L2 — 2025/2026
======================================================

PRÉREQUIS
---------
- PHP 8.1 ou supérieur (avec extensions : json, session)
- Serveur web : Apache (XAMPP / WAMP / LAMP) ou PHP built-in server
- Navigateur moderne avec accès caméra (HTTPS requis pour la caméra en production)
- Aucune base de données requise

DÉPLOIEMENT LOCAL (méthode rapide — serveur intégré PHP)
---------------------------------------------------------
1. Placez le dossier `facturation/` dans votre répertoire web
   (ex: C:\xampp\htdocs\facturation  ou  /var/www/html/facturation)

2. Vérifiez les permissions d'écriture sur le dossier data/ :
   chmod 775 facturation/data/

3. Démarrez le serveur PHP intégré depuis la racine du projet :
   php -S localhost:8080 -t facturation/

4. Ouvrez votre navigateur : http://localhost:8080/

DÉPLOIEMENT APACHE (XAMPP)
---------------------------
1. Copiez le dossier dans htdocs/facturation/
2. Activez mod_rewrite si nécessaire
3. Accédez via http://localhost/facturation/

CONNEXION PAR DÉFAUT
---------------------
  Identifiant : admin
  Mot de passe : password


STRUCTURE DES DONNÉES
----------------------
  data/produits.json    — Catalogue produits
  data/factures.json    — Historique des factures
  data/utilisateurs.json — Comptes utilisateurs

RÔLES
------
  superadmin : gestion complète (comptes, produits, facturation, rapports)
  manager    : produits, facturation, rapports
  caissier   : facturation uniquement

LECTURE DE CODES-BARRES
------------------------
- Nécessite un accès caméra (HTTPS en production, localhost en dev)
- Bibliothèque utilisée : QuaggaJS (CDN cdnjs.cloudflare.com)
- Saisie manuelle toujours disponible en fallback
