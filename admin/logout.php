<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (isset($_SESSION['utilisateur_id'])) {
    // Détruire toutes les données de session
    session_unset();
    session_destroy();

    // Rediriger vers la page de connexion ou la page d'accueil après la déconnexion
    header("Location: index.php");
    exit();
} else {
    // Si l'utilisateur n'est pas connecté, le rediriger vers la page d'accueil
    header("Location: index.php");
    exit();
}
?>
