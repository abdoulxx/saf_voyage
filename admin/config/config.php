<?php
// Configuration de la base de données
$host = 'localhost';  // Adresse du serveur MySQL
$dbname = 'saf_voyage'; // Nom de la base de données
$username = 'root';   // Nom d'utilisateur MySQL
$password = '';       // Mot de passe MySQL

// Connexion à la base de données avec PDO
try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Définir le mode d'erreur PDO sur Exception pour afficher les erreurs
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Erreur de connexion : ' . $e->getMessage();
    exit;
}
?>
