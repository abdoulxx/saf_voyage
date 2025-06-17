<?php
session_start();
require_once 'config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Traitement de l'annulation de réservation
if (isset($_GET['action']) && $_GET['action'] === 'annuler' && isset($_GET['id'])) {
    $reservation_id = intval($_GET['id']);
    
    try {
        // Vérifier que la réservation appartient à l'utilisateur et qu'elle est en attente
        $query = "SELECT * FROM reservations WHERE id = :reservation_id AND user_id = :user_id AND statut_paiement IN ('en_attente', 'non_paye')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':reservation_id', $reservation_id);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation) {
            // Mettre à jour le statut de la réservation
            $query = "UPDATE reservations SET statut_paiement = 'annule' WHERE id = :reservation_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':reservation_id', $reservation_id);
            $stmt->execute();
            
            $_SESSION['success'] = "Votre réservation a été annulée avec succès.";
        } else {
            $_SESSION['error'] = "Impossible d'annuler cette réservation. Elle n'est peut-être pas en attente ou ne vous appartient pas.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de l'annulation de la réservation: " . $e->getMessage();
    }
    
    header("Location: mes_reservations.php");
    exit;
}

// Récupérer les réservations de l'utilisateur
try {
    $query = "SELECT r.*, v.destination, v.date_depart, v.date_retour, v.duree, 
              h.nom as hotel_nom, h.localisation as hotel_localisation
              FROM reservations r
              LEFT JOIN vols v ON r.vol_id = v.id
              LEFT JOIN hotels h ON r.hotel_id = h.id
              WHERE r.user_id = :user_id
              ORDER BY r.date_reservation DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des réservations: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Mes réservations - Saf Voyage">
    <title>Mes Réservations - Saf Voyage</title>
    <!-- Intégration de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .reservations-container {
            padding: 50px 20px;
            background-color: #f8f8f8;
        }
        
        .reservation-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
            transition: transform 0.3s ease;
        }
        
        .reservation-card:hover {
            transform: translateY(-5px);
        }
        
        .reservation-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .reservation-destination {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0066cc;
        }
        
        .reservation-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .status-paye {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-en-attente {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-non-paye {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-annule {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .reservation-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .reservation-info {
            flex: 1;
            min-width: 250px;
        }
        
        .reservation-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .no-reservations {
            text-align: center;
            padding: 50px 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .no-reservations i {
            font-size: 60px;
            color: #6c757d;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- En-tête -->
    <?php include('includes/head.php'); ?>
    <?php include('includes/navbar.php'); ?>

    <!-- Section Mes Réservations -->
    <section class="reservations-container">
        <div class="container">
            <h2 class="text-center mb-4">Mes Réservations</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info'])): ?>
                <div class="alert alert-info"><?php echo $_SESSION['info']; unset($_SESSION['info']); ?></div>
            <?php endif; ?>
            
            <?php if (empty($reservations)): ?>
                <div class="no-reservations">
                    <i class="fas fa-suitcase-rolling"></i>
                    <h3>Vous n'avez pas encore de réservations</h3>
                    <p>Explorez nos destinations et planifiez votre prochain voyage dès maintenant!</p>
                    <a href="index.php" class="btn btn-primary mt-3">Découvrir nos destinations</a>
                </div>
            <?php else: ?>
                <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="reservation-header">
                            <div class="reservation-destination">
                                <?php echo ucfirst($reservation['destination']); ?>
                            </div>
                            <div class="reservation-status status-<?php echo str_replace('_', '-', $reservation['statut_paiement']); ?>">
                                <?php 
                                switch($reservation['statut_paiement']) {
                                    case 'paye':
                                        echo 'Payé';
                                        break;
                                    case 'en_attente':
                                        echo 'En attente';
                                        break;
                                    case 'non_paye':
                                        echo 'Non payé';
                                        break;
                                    case 'annule':
                                        echo 'Annulé';
                                        break;
                                    default:
                                        echo $reservation['statut_paiement'];
                                }
                                ?>
                            </div>
                        </div>
                        
                        <div class="reservation-details">
                            <div class="reservation-info">
                                <p><strong>Référence:</strong> #<?php echo $reservation['id']; ?></p>
                                <p><strong>Date de réservation:</strong> <?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></p>
                                <p><strong>Dates du voyage:</strong> Du <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?> au <?php echo date('d/m/Y', strtotime($reservation['date_retour'])); ?> (<?php echo $reservation['duree']; ?> jours)</p>
                            </div>
                            
                            <div class="reservation-info">
                                <?php if ($reservation['hotel_id']): ?>
                                <p><strong>Hôtel:</strong> <?php echo $reservation['hotel_nom']; ?></p>
                                <p><strong>Localisation:</strong> <?php echo $reservation['hotel_localisation']; ?></p>
                                <?php else: ?>
                                <p><strong>Hébergement:</strong> Aucun hôtel sélectionné</p>
                                <?php endif; ?>
                                
                                <p><strong>Méthode de paiement:</strong> <?php echo ($reservation['methode_paiement'] === 'reception') ? 'Paiement à la réception' : 'Paiement en ligne'; ?></p>
                                <p><strong>Prix total:</strong> <?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</p>
                            </div>
                        </div>
                        
                        <div class="reservation-actions">
                            <a href="confirmation-reservation.php?id=<?php echo $reservation['id']; ?>" class="btn btn-outline-primary">Voir les détails</a>
                            
                            <?php if ($reservation['statut_paiement'] === 'non_paye'): ?>
                            <a href="paiement.php?id=<?php echo $reservation['id']; ?>" class="btn btn-success">Procéder au paiement</a>
                            <?php endif; ?>
                            
                            <?php if ($reservation['statut_paiement'] === 'paye'): ?>
                            <a href="facture-pdf.php?id=<?php echo $reservation['id']; ?>" class="btn btn-info" target="_blank">
                                <i class="fas fa-file-pdf"></i> Télécharger la facture
                            </a>
                            <?php endif; ?>
                            
                            <?php if (in_array($reservation['statut_paiement'], ['en_attente', 'non_paye'])): ?>
                            <a href="mes_reservations.php?action=annuler&id=<?php echo $reservation['id']; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">
                                <i class="fas fa-times-circle"></i> Annuler la réservation
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include('includes/footer.php'); ?>

    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <!-- Intégration de Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>