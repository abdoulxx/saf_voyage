<?php
session_start();
require_once 'config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Vérifier si l'ID de réservation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$reservation_id = intval($_GET['id']);

// Récupérer les détails de la réservation
try {
    $query = "SELECT r.*, v.destination, v.date_depart, v.date_retour, v.duree, 
              h.nom as hotel_nom, h.localisation as hotel_localisation
              FROM reservations r
              LEFT JOIN vols v ON r.vol_id = v.id
              LEFT JOIN hotels h ON r.hotel_id = h.id
              WHERE r.id = :reservation_id AND r.user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reservation_id', $reservation_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        $_SESSION['error'] = "Réservation introuvable.";
        header("Location: index.php");
        exit;
    }
    
    // Récupérer les options de la réservation
    $query = "SELECT option_nom FROM reservation_options WHERE reservation_id = :reservation_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reservation_id', $reservation_id);
    $stmt->execute();
    $options = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des détails de la réservation: " . $e->getMessage();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Confirmation de votre réservation - Saf Voyage">
    <title>Confirmation de Réservation - Saf Voyage</title>
    <!-- Intégration de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .success-icon {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon i {
            font-size: 80px;
            color: #28a745;
        }
        
        .confirmation-title {
            text-align: center;
            color: #0066cc;
            margin-bottom: 30px;
        }
        
        .reservation-details {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .action-buttons {
            margin-top: 40px;
            text-align: center;
        }
        
        .action-buttons .btn {
            margin: 0 10px;
            padding: 10px 25px;
        }
    </style>
</head>
<body>

    <!-- En-tête -->
    <?php include('includes/head.php'); ?>
    <?php include('includes/navbar.php'); ?>

    <!-- Section Confirmation -->
    <div class="container">
        <div class="confirmation-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="confirmation-title">Réservation Confirmée!</h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <p class="text-center">Votre réservation a été enregistrée avec succès. Voici les détails de votre voyage :</p>
            
            <div class="reservation-details">
                <h4>Détails de la réservation #<?php echo $reservation_id; ?></h4>
                <p><strong>Destination:</strong> <?php echo ucfirst($reservation['destination']); ?></p>
                <p><strong>Date de départ:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></p>
                <p><strong>Date de retour:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_retour'])); ?></p>
                <p><strong>Durée du séjour:</strong> <?php echo $reservation['duree']; ?> jours</p>
                
                <?php if ($reservation['hotel_id']): ?>
                <p><strong>Hôtel:</strong> <?php echo $reservation['hotel_nom']; ?></p>
                <p><strong>Localisation:</strong> <?php echo $reservation['hotel_localisation']; ?></p>
                <?php else: ?>
                <p><strong>Hébergement:</strong> Aucun hôtel sélectionné</p>
                <?php endif; ?>
                
                <?php if (!empty($options)): ?>
                <p><strong>Options:</strong></p>
                <ul>
                    <?php foreach ($options as $option): ?>
                    <li><?php echo $option; ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                
                <p><strong>Prix total:</strong> <?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</p>
                <p><strong>Méthode de paiement:</strong> <?php echo ($reservation['methode_paiement'] === 'reception') ? 'Paiement à la réception' : 'Paiement en ligne'; ?></p>
                <p><strong>Statut du paiement:</strong> 
                    <?php 
                    switch($reservation['statut_paiement']) {
                        case 'paye':
                            echo '<span class="text-success">Payé</span>';
                            break;
                        case 'en_attente':
                            echo '<span class="text-warning">En attente</span>';
                            break;
                        case 'non_paye':
                            echo '<span class="text-danger">Non payé</span>';
                            break;
                        default:
                            echo $reservation['statut_paiement'];
                    }
                    ?>
                </p>
            </div>
            
            <div class="action-buttons">
                <a href="mes_reservations.php" class="btn btn-primary">Voir mes réservations</a>
                <a href="index.php" class="btn btn-outline-secondary">Retour à l'accueil</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include('includes/footer.php'); ?>

    <!-- FontAwesome pour l'icône de succès -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <!-- Intégration de Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
