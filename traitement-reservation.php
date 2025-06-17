<?php
session_start();
require_once 'config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Récupérer les données du formulaire
$vol_id = isset($_POST['vol_id']) ? intval($_POST['vol_id']) : 0;
$hotel_id = isset($_POST['hotel']) ? intval($_POST['hotel']) : null;
$options_selected = isset($_POST['options']) ? $_POST['options'] : [];
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$prix_total = isset($_POST['prix_total']) ? floatval($_POST['prix_total']) : 0;

// Vérifier que le vol existe
try {
    $query = "SELECT * FROM vols WHERE id = :vol_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vol_id', $vol_id);
    $stmt->execute();
    $vol = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vol) {
        $_SESSION['error'] = "Le vol sélectionné n'existe pas.";
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la vérification du vol: " . $e->getMessage();
    header("Location: reservation-details.php?vol_id=" . $vol_id);
    exit;
}

// Créer la réservation dans la base de données
try {
    $db->beginTransaction();
    
    // Insérer la réservation principale
    $query = "INSERT INTO reservations (user_id, vol_id, hotel_id, prix_total, statut_paiement, methode_paiement, date_reservation) 
              VALUES (:user_id, :vol_id, :hotel_id, :prix_total, :statut_paiement, :methode_paiement, NOW())";
    
    $stmt = $db->prepare($query);
    $user_id = $_SESSION['user_id'];
    
    // Définir le statut de paiement en fonction de la méthode choisie
    $statut_paiement = ($payment_method === 'reception') ? 'en_attente' : 'non_paye';
    
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':vol_id', $vol_id);
    $stmt->bindParam(':hotel_id', $hotel_id, PDO::PARAM_INT);
    $stmt->bindParam(':prix_total', $prix_total);
    $stmt->bindParam(':statut_paiement', $statut_paiement);
    $stmt->bindParam(':methode_paiement', $payment_method);
    $stmt->execute();
    
    // Récupérer l'ID de la réservation créée
    $reservation_id = $db->lastInsertId();
    
    // Enregistrer les options sélectionnées
    if (!empty($options_selected)) {
        $query = "INSERT INTO reservation_options (reservation_id, option_nom) VALUES (:reservation_id, :option_nom)";
        $stmt = $db->prepare($query);
        
        foreach ($options_selected as $option) {
            $stmt->bindParam(':reservation_id', $reservation_id);
            $stmt->bindParam(':option_nom', $option);
            $stmt->execute();
        }
    }
    
    $db->commit();
    
    // Rediriger en fonction de la méthode de paiement
    if ($payment_method === 'reception') {
        // Paiement à la réception, rediriger vers la page de confirmation
        $_SESSION['success'] = "Votre réservation a été enregistrée avec succès. Vous paierez à la réception du billet d'avion.";
        header("Location: confirmation-reservation.php?id=" . $reservation_id);
        exit;
    } else {
        // Paiement en ligne, rediriger vers la page de paiement PayTech
        $_SESSION['reservation_id'] = $reservation_id;
        header("Location: paiement.php?id=" . $reservation_id);
        exit;
    }
    
} catch (PDOException $e) {
    $db->rollBack();
    $_SESSION['error'] = "Erreur lors de l'enregistrement de la réservation: " . $e->getMessage();
    header("Location: reservation-details.php?vol_id=" . $vol_id);
    exit;
}
?>
