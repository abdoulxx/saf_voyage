<?php
session_start();
require_once 'config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Récupérer la référence de paiement depuis l'URL (fournie par PayTech)
$ref_payment = isset($_GET['ref_payment']) ? $_GET['ref_payment'] : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($ref_payment) || empty($token)) {
    $_SESSION['error'] = "Informations de paiement manquantes.";
    header("Location: mes_reservations.php");
    exit;
}

// Vérifier le paiement dans la base de données
try {
    $query = "SELECT * FROM reservations WHERE reference_paiement = :reference AND user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reference', $ref_payment);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        $_SESSION['error'] = "Réservation introuvable avec cette référence de paiement.";
        header("Location: mes_reservations.php");
        exit;
    }
    
    // Mettre à jour le statut de paiement
    $query = "UPDATE reservations SET statut_paiement = 'paye', date_paiement = NOW() WHERE id = :reservation_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reservation_id', $reservation['id']);
    $stmt->execute();
    
    // Rediriger vers la page de confirmation
    $_SESSION['success'] = "Votre paiement a été effectué avec succès. Votre réservation est confirmée!";
    header("Location: confirmation-reservation.php?id=" . $reservation['id']);
    exit;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la vérification du paiement: " . $e->getMessage();
    header("Location: mes_reservations.php");
    exit;
}
?>
