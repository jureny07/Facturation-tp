<?php
// auth/logout.php — Déconnexion

require_once __DIR__ . '/../auth/session.php';
session_destroy();
header('Location: /facturation/auth/login.php');
exit;
