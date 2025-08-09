<?php
// Script d'installation
require_once 'config.php';

$sql = file_get_contents('database_complete.sql');

try {
    $db = getDB();
    
    // Exécuter le script SQL
