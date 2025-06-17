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
    
    // Vérifier que la réservation n'est pas déjà payée
    if ($reservation['statut_paiement'] === 'paye') {
        $_SESSION['info'] = "Cette réservation a déjà été payée.";
        header("Location: confirmation-reservation.php?id=" . $reservation_id);
        exit;
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des détails de la réservation: " . $e->getMessage();
    header("Location: index.php");
    exit;
}

// Configuration de KkiaPay
$kkiapay_public_key = '6d0d9ef01e0e11f0b28afb2ba2f61f55';
$kkiapay_private_key = 'tpk_6d0dc6001e0e11f0b28afb2ba2f61f55';
$kkiapay_secret = 'tsk_6d0dc6011e0e11f0b28afb2ba2f61f55';

// Générer une référence unique pour le paiement
$reference = "RES" . $reservation_id . "_" . time();

// Stocker la référence de paiement dans la base de données
try {
    $query = "UPDATE reservations SET reference_paiement = :reference WHERE id = :reservation_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reference', $reference);
    $stmt->bindParam(':reservation_id', $reservation_id);
    $stmt->execute();
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la mise à jour de la référence de paiement: " . $e->getMessage();
    header("Location: paiement.php?id=" . $reservation_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Paiement de votre réservation - Saf Voyage">
    <title>Paiement - Saf Voyage</title>
    <!-- Intégration de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <!-- Intégration de KkiaPay Widget -->
    <script src="https://cdn.kkiapay.me/k.js"></script>
    <style>
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .payment-title {
            text-align: center;
            color: #0066cc;
            margin-bottom: 30px;
        }
        
        .reservation-summary {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .payment-options {
            margin-top: 30px;
            text-align: center;
        }
        
        .payment-btn {
            margin-top: 20px;
            padding: 12px 30px;
            font-size: 1.1rem;
            background-color: rgba(6, 19, 116, 0.47);
            border: none;
        }
        
        .payment-btn:hover {
            background-color: rgb(10, 31, 122);
        }
        
        .payment-secure {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .payment-secure i {
            color: #28a745;
            margin-right: 5px;
        }
    </style>
</head>
<body>

    <!-- En-tête -->
    <?php include('includes/head.php'); ?>
    <?php include('includes/navbar.php'); ?>

    <!-- Section Paiement -->
    <div class="container">
        <div class="payment-container">
            <h1 class="payment-title">Paiement de votre réservation</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="reservation-summary">
                <h4>Récapitulatif de la réservation #<?php echo $reservation_id; ?></h4>
                <p><strong>Destination:</strong> <?php echo ucfirst($reservation['destination']); ?></p>
                <p><strong>Date de départ:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></p>
                <p><strong>Date de retour:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_retour'])); ?></p>
                <p><strong>Durée du séjour:</strong> <?php echo $reservation['duree']; ?> jours</p>
                
                <?php if ($reservation['hotel_id']): ?>
                <p><strong>Hôtel:</strong> <?php echo $reservation['hotel_nom']; ?></p>
                <?php else: ?>
                <p><strong>Hébergement:</strong> Aucun hôtel sélectionné</p>
                <?php endif; ?>
                
                <p><strong>Montant à payer:</strong> <span class="fw-bold text-primary"><?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</span></p>
            </div>
            
            <div class="payment-options">
                <h4>Procéder au paiement</h4>
                <p>Vous allez être redirigé vers la plateforme sécurisée KkiaPay pour effectuer votre paiement.</p>
                
                <button id="kkiapay-button" class="btn btn-primary payment-btn">
                    Payer maintenant
                </button>
                
                <div class="payment-secure">
                    <i class="fas fa-lock"></i> Paiement 100% sécurisé via KkiaPay
                </div>
                
                <div class="mt-4">
                    <a href="mes_reservations.php" class="btn btn-outline-secondary">Annuler et revenir à mes réservations</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include('includes/footer.php'); ?>

    <!-- FontAwesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <!-- Intégration de Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script KkiaPay -->
    <script>
        window.onload = () => {
            document
                .getElementById('kkiapay-button')
                .addEventListener('click', () => {
                    const amount = <?php echo (int) $reservation['prix_total']; ?>;
                    const apiKey = <?php echo json_encode($kkiapay_public_key); ?>;
                    const successUrl = <?php echo json_encode('paiement-success.php?ref_payment=' . $reference); ?>;
                    const cancelUrl = <?php echo json_encode('paiement-cancel.php?ref_payment=' . $reference); ?>;
                    const customData = {
                        reservation_id: <?php echo $reservation_id; ?>,
                        reference: <?php echo json_encode($reference); ?>
                    };

                    openKkiapayWidget({
                        amount: amount,
                        key: apiKey,
                        sandbox: true, // false en production
                        callback: successUrl,
                        data: customData,
                        position: 'center',
                        theme: 'blue'
                    });
                });

            // Écouter le succès et stocker les informations
            addKkiapayListener('success', resp => {
                console.log('Transaction OK :', resp.transactionId);
                // Rediriger vers la page de succès avec l'ID de transaction
                window.location.href = 'paiement-success.php?ref_payment=<?php echo $reference; ?>&token=' + resp.transactionId;
            });

            // Écouter l'échec
            addKkiapayListener('failed', resp => {
                console.log('Transaction échouée :', resp);
                window.location.href = 'paiement-cancel.php?ref_payment=<?php echo $reference; ?>';
            });
        };
    </script>

</body>
</html>
