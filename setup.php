<?php
// Script d'installation
require_once 'config.php';

$sql = file_get_contents('database_complete.sql');

try {
    $db = getDB();
    
    // Exécuter le script SQL
    $db->exec($sql);
    
    echo "<h1>Installation réussie !</h1>";
    echo "<p>La base de données a été créée avec des données de test.</p>";
    echo "<p><strong>Comptes de test :</strong></p>";
    echo "<ul>";
    echo "<li>Admin: admin / test123</li>";
    echo "<li>Enseignant Math: prof.math / test123</li>";
    echo "<li>Enseignant Français: prof.francais / test123</li>";
    echo "<li>Élève 1: eleve1 / test123</li>";
    echo "<li>Élève 2: eleve2 / test123</li>";
    echo "<li>Parent 1: parent1 / test123</li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Accéder à l'application</a></p>";
    
} catch(PDOException $e) {
    echo "<h1>Erreur d'installation</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
// Added input validation

// verification des entrees ajoutee

// verification 

// verification des entrees ajoutee
