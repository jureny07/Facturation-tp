<?php
// auth/session.php — Initialisation et vérification de session

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions-auth.php';
