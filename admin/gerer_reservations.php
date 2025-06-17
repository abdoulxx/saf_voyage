<?php
session_start();
include('../config/config.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Initialiser les variables
$action = isset($_GET['action']) ? $_GET['action'] : '';
$reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$reservation = [];
$error = '';
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement du formulaire de modification
    if (isset($_POST['save_reservation'])) {
        $statut_paiement = $_POST['statut_paiement'];
        
        try {
            // Mise à jour du statut de la réservation
            $query = "UPDATE reservations SET statut_paiement = :statut_paiement WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':statut_paiement', $statut_paiement);
            $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Si le statut est "payé", mettre à jour la date de paiement
            if ($statut_paiement === 'paye') {
                $query = "UPDATE reservations SET date_paiement = NOW() WHERE id = :id AND date_paiement IS NULL";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            $success = "La réservation a été mise à jour avec succès.";
            header("Location: liste_reservations.php?success=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
}

// Suppression d'une réservation
if ($action === 'supprimer' && $reservation_id > 0) {
    try {
        // Supprimer d'abord les options de réservation associées
        $query = "DELETE FROM reservation_options WHERE reservation_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $stmt->execute();
        
        // Puis supprimer la réservation
        $query = "DELETE FROM reservations WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $success = "La réservation a été supprimée avec succès.";
        header("Location: liste_reservations.php?success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

// Récupérer les informations de la réservation pour modification ou visualisation
if (($action === 'modifier' || $action === 'voir') && $reservation_id > 0) {
    try {
        $query = "SELECT r.*, v.destination, v.date_depart, v.date_retour, v.duree, 
                  h.nom as hotel_nom, h.localisation as hotel_localisation, h.prix_nuit as hotel_prix,
                  u.nom as user_nom, u.email as user_email, u.numero_telephone as user_telephone
                  FROM reservations r
                  LEFT JOIN vols v ON r.vol_id = v.id
                  LEFT JOIN hotels h ON r.hotel_id = h.id
                  LEFT JOIN users u ON r.user_id = u.id
                  WHERE r.id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $reservation_id, PDO::PARAM_INT);
        $stmt->execute();
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reservation) {
            $error = "Réservation introuvable.";
            $action = '';
        } else {
            // Récupérer les options de la réservation
            $query = "SELECT option_nom FROM reservation_options WHERE reservation_id = :reservation_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':reservation_id', $reservation_id);
            $stmt->execute();
            $options = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $reservation['options'] = $options;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des informations: " . $e->getMessage();
        $action = '';
    }
}

// Titre de la page en fonction de l'action
$page_title = '';
switch ($action) {
    case 'modifier':
        $page_title = "Modifier la réservation";
        break;
    case 'voir':
        $page_title = "Détails de la réservation";
        break;
    default:
        header("Location: liste_reservations.php");
        exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .reservation-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .reservation-info {
            margin-bottom: 15px;
        }
        
        .reservation-info h5 {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .badge {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
        }
    </style>
</head>

<body>
    <?php include '../admin/top-navbar.php'; ?>
    <?php include '../admin/sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
                <a href="liste_reservations.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Retour à la liste
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-body">
                    <?php if (!empty($reservation)): ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="reservation-info">
                                    <h5>Informations client</h5>
                                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($reservation['user_nom']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($reservation['user_email']); ?></p>
                                    <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($reservation['user_telephone']); ?></p>
                                </div>
                                
                                <div class="reservation-info">
                                    <h5>Détails du voyage</h5>
                                    <p><strong>Destination:</strong> <?php echo ucfirst(htmlspecialchars($reservation['destination'])); ?></p>
                                    <p><strong>Date de départ:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></p>
                                    <p><strong>Date de retour:</strong> <?php echo date('d/m/Y', strtotime($reservation['date_retour'])); ?></p>
                                    <p><strong>Durée:</strong> <?php echo $reservation['duree']; ?> jours</p>
                                </div>
                                
                                <?php if ($reservation['hotel_id']): ?>
                                <div class="reservation-info">
                                    <h5>Hébergement</h5>
                                    <p><strong>Hôtel:</strong> <?php echo htmlspecialchars($reservation['hotel_nom']); ?></p>
                                    <p><strong>Localisation:</strong> <?php echo htmlspecialchars($reservation['hotel_localisation']); ?></p>
                                    <p><strong>Prix par nuit:</strong> <?php echo number_format($reservation['hotel_prix'], 0, ',', ' '); ?> FCFA</p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($reservation['options'])): ?>
                                <div class="reservation-info">
                                    <h5>Options</h5>
                                    <ul>
                                        <?php foreach ($reservation['options'] as $option): ?>
                                            <li><?php echo htmlspecialchars($option); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="reservation-info">
                                    <h5>Informations de paiement</h5>
                                    <p><strong>Prix total:</strong> <?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</p>
                                    <p><strong>Méthode de paiement:</strong> <?php echo $reservation['methode_paiement'] === 'reception' ? 'Paiement à la réception' : 'Paiement en ligne'; ?></p>
                                    <p>
                                        <strong>Statut du paiement:</strong> 
                                        <?php 
                                        $status_class = '';
                                        $status_text = '';
                                        
                                        switch($reservation['statut_paiement']) {
                                            case 'paye':
                                                $status_class = 'success';
                                                $status_text = 'Payé';
                                                break;
                                            case 'en_attente':
                                                $status_class = 'warning';
                                                $status_text = 'En attente';
                                                break;
                                            case 'non_paye':
                                                $status_class = 'danger';
                                                $status_text = 'Non payé';
                                                break;
                                            case 'annule':
                                                $status_class = 'secondary';
                                                $status_text = 'Annulé';
                                                break;
                                            default:
                                                $status_class = 'info';
                                                $status_text = $reservation['statut_paiement'];
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </p>
                                    <?php if ($reservation['date_paiement']): ?>
                                        <p><strong>Date de paiement:</strong> <?php echo date('d/m/Y H:i', strtotime($reservation['date_paiement'])); ?></p>
                                    <?php endif; ?>
                                    <p><strong>Référence de paiement:</strong> <?php echo $reservation['reference_paiement'] ? htmlspecialchars($reservation['reference_paiement']) : 'Non disponible'; ?></p>
                                </div>
                                
                                <div class="reservation-info">
                                    <h5>Dates</h5>
                                    <p><strong>Date de réservation:</strong> <?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></p>
                                </div>
                                
                                <?php if ($action === 'modifier'): ?>
                                    <div class="reservation-info">
                                        <h5>Modifier le statut</h5>
                                        <form method="POST" action="">
                                            <div class="mb-3">
                                                <label for="statut_paiement" class="form-label">Statut du paiement</label>
                                                <select class="form-select" id="statut_paiement" name="statut_paiement">
                                                    <option value="paye" <?php echo $reservation['statut_paiement'] === 'paye' ? 'selected' : ''; ?>>Payé</option>
                                                    <option value="en_attente" <?php echo $reservation['statut_paiement'] === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                                    <option value="non_paye" <?php echo $reservation['statut_paiement'] === 'non_paye' ? 'selected' : ''; ?>>Non payé</option>
                                                    <option value="annule" <?php echo $reservation['statut_paiement'] === 'annule' ? 'selected' : ''; ?>>Annulé</option>
                                                </select>
                                            </div>
                                            <button type="submit" name="save_reservation" class="btn btn-primary">
                                                <i class="bi bi-save"></i> Enregistrer les modifications
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <div class="d-flex gap-2">
                                        <?php if ($action === 'voir'): ?>
                                            <a href="gerer_reservations.php?action=modifier&id=<?php echo $reservation_id; ?>" class="btn btn-primary">
                                                <i class="bi bi-pencil"></i> Modifier
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="../facture-pdf.php?id=<?php echo $reservation_id; ?>" class="btn btn-success" target="_blank">
                                            <i class="bi bi-file-pdf"></i> Télécharger la facture
                                        </a>
                                        
                                        <a href="gerer_reservations.php?action=supprimer&id=<?php echo $reservation_id; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Aucune information disponible pour cette réservation.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
