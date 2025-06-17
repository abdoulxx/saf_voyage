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
$vol_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$vol = [];
$error = '';
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement du formulaire d'ajout ou de modification
    if (isset($_POST['save_vol'])) {
        $destination = $_POST['destination'];
        $date_depart = $_POST['date_depart'];
        $date_retour = $_POST['date_retour'];
        $prix_base = $_POST['prix_base'];
        $places_disponibles = $_POST['places_disponibles'];
        $description = $_POST['description'];
        $image = $_POST['image'];
        
        // Calculer la durée en jours
        $date1 = new DateTime($date_depart);
        $date2 = new DateTime($date_retour);
        $interval = $date1->diff($date2);
        $duree = $interval->days;
        
        try {
            if ($action === 'ajouter') {
                // Ajout d'un nouveau vol
                $query = "INSERT INTO vols (destination, date_depart, date_retour, duree, prix_base, places_disponibles, description, image) 
                          VALUES (:destination, :date_depart, :date_retour, :duree, :prix_base, :places_disponibles, :description, :image)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':destination', $destination);
                $stmt->bindParam(':date_depart', $date_depart);
                $stmt->bindParam(':date_retour', $date_retour);
                $stmt->bindParam(':duree', $duree);
                $stmt->bindParam(':prix_base', $prix_base);
                $stmt->bindParam(':places_disponibles', $places_disponibles);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':image', $image);
                $stmt->execute();
                
                $success = "Le vol a été ajouté avec succès.";
                header("Location: liste-vols.php?success=" . urlencode($success));
                exit;
            } else if ($action === 'modifier' && $vol_id > 0) {
                // Modification d'un vol existant
                $query = "UPDATE vols SET 
                          destination = :destination, 
                          date_depart = :date_depart, 
                          date_retour = :date_retour, 
                          duree = :duree, 
                          prix_base = :prix_base, 
                          places_disponibles = :places_disponibles, 
                          description = :description, 
                          image = :image 
                          WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':destination', $destination);
                $stmt->bindParam(':date_depart', $date_depart);
                $stmt->bindParam(':date_retour', $date_retour);
                $stmt->bindParam(':duree', $duree);
                $stmt->bindParam(':prix_base', $prix_base);
                $stmt->bindParam(':places_disponibles', $places_disponibles);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':image', $image);
                $stmt->bindParam(':id', $vol_id, PDO::PARAM_INT);
                $stmt->execute();
                
                $success = "Le vol a été mis à jour avec succès.";
                header("Location: liste-vols.php?success=" . urlencode($success));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'enregistrement: " . $e->getMessage();
        }
    }
}

// Suppression d'un vol
if ($action === 'supprimer' && $vol_id > 0) {
    try {
        // Vérifier si le vol est utilisé dans des réservations
        $query = "SELECT COUNT(*) as count FROM reservations WHERE vol_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $vol_id, PDO::PARAM_INT);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($count > 0) {
            $error = "Ce vol ne peut pas être supprimé car il est associé à $count réservation(s).";
        } else {
            // Supprimer le vol
            $query = "DELETE FROM vols WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $vol_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "Le vol a été supprimé avec succès.";
            header("Location: liste-vols.php?success=" . urlencode($success));
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

// Récupérer les informations du vol pour modification ou visualisation
if (($action === 'modifier' || $action === 'voir') && $vol_id > 0) {
    try {
        $query = "SELECT * FROM vols WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $vol_id, PDO::PARAM_INT);
        $stmt->execute();
        $vol = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$vol) {
            $error = "Vol introuvable.";
            $action = '';
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des informations: " . $e->getMessage();
        $action = '';
    }
}

// Titre de la page en fonction de l'action
$page_title = '';
switch ($action) {
    case 'ajouter':
        $page_title = "Ajouter un vol";
        break;
    case 'modifier':
        $page_title = "Modifier le vol";
        break;
    case 'voir':
        $page_title = "Détails du vol";
        break;
    default:
        header("Location: liste-vols.php");
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
        .vol-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .vol-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .vol-info {
            margin-bottom: 15px;
        }
        
        .vol-info h5 {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 15px;
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
                <a href="liste-vols.php" class="btn btn-secondary">
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
                    <?php if ($action === 'ajouter' || $action === 'modifier'): ?>
                        <!-- Formulaire d'ajout/modification -->
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="destination" class="form-label">Destination</label>
                                        <input type="text" class="form-control" id="destination" name="destination" value="<?php echo isset($vol['destination']) ? htmlspecialchars($vol['destination']) : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="date_depart" class="form-label">Date de départ</label>
                                        <input type="date" class="form-control" id="date_depart" name="date_depart" value="<?php echo isset($vol['date_depart']) ? $vol['date_depart'] : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="date_retour" class="form-label">Date de retour</label>
                                        <input type="date" class="form-control" id="date_retour" name="date_retour" value="<?php echo isset($vol['date_retour']) ? $vol['date_retour'] : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="prix_base" class="form-label">Prix de base (FCFA)</label>
                                        <input type="number" class="form-control" id="prix_base" name="prix_base" value="<?php echo isset($vol['prix_base']) ? $vol['prix_base'] : ''; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="places_disponibles" class="form-label">Places disponibles</label>
                                        <input type="number" class="form-control" id="places_disponibles" name="places_disponibles" value="<?php echo isset($vol['places_disponibles']) ? $vol['places_disponibles'] : ''; ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($vol['description']) ? htmlspecialchars($vol['description']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">URL de l'image</label>
                                        <input type="text" class="form-control" id="image" name="image" value="<?php echo isset($vol['image']) ? htmlspecialchars($vol['image']) : ''; ?>">
                                        <?php if (isset($vol['image']) && !empty($vol['image'])): ?>
                                            <div class="mt-2">
                                                <img src="<?php echo htmlspecialchars($vol['image']); ?>" alt="Aperçu" class="img-thumbnail" style="max-height: 100px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" name="save_vol" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                                <a href="liste-vols.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    <?php elseif ($action === 'voir' && !empty($vol)): ?>
                        <!-- Affichage des détails du vol -->
                        <div class="row">
                            <div class="col-md-6">
                                <?php if (!empty($vol['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($vol['image']); ?>" alt="<?php echo htmlspecialchars($vol['destination']); ?>" class="vol-image">
                                <?php endif; ?>
                                
                                <div class="vol-info">
                                    <h5>Informations générales</h5>
                                    <p><strong>Destination:</strong> <?php echo ucfirst(htmlspecialchars($vol['destination'])); ?></p>
                                    <p><strong>Date de départ:</strong> <?php echo date('d/m/Y', strtotime($vol['date_depart'])); ?></p>
                                    <p><strong>Date de retour:</strong> <?php echo date('d/m/Y', strtotime($vol['date_retour'])); ?></p>
                                    <p><strong>Durée:</strong> <?php echo $vol['duree']; ?> jours</p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="vol-info">
                                    <h5>Détails</h5>
                                    <p><strong>Prix de base:</strong> <?php echo number_format($vol['prix_base'], 0, ',', ' '); ?> FCFA</p>
                                    <p><strong>Places disponibles:</strong> <?php echo $vol['places_disponibles']; ?></p>
                                    <?php if (!empty($vol['description'])): ?>
                                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($vol['description'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="vol-info">
                                    <h5>Statistiques</h5>
                                    <?php
                                    try {
                                        // Nombre de réservations pour ce vol
                                        $query = "SELECT COUNT(*) as count FROM reservations WHERE vol_id = :id";
                                        $stmt = $db->prepare($query);
                                        $stmt->bindParam(':id', $vol_id, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $reservations_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                        
                                        // Montant total des réservations
                                        $query = "SELECT SUM(prix_total) as total FROM reservations WHERE vol_id = :id AND statut_paiement = 'paye'";
                                        $stmt = $db->prepare($query);
                                        $stmt->bindParam(':id', $vol_id, PDO::PARAM_INT);
                                        $stmt->execute();
                                        $total_amount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                        if ($total_amount === null) $total_amount = 0;
                                    } catch (PDOException $e) {
                                        $reservations_count = 0;
                                        $total_amount = 0;
                                    }
                                    ?>
                                    <p><strong>Nombre de réservations:</strong> <?php echo $reservations_count; ?></p>
                                    <p><strong>Montant total des réservations:</strong> <?php echo number_format($total_amount, 0, ',', ' '); ?> FCFA</p>
                                </div>
                                
                                <div class="mt-4">
                                    <div class="d-flex gap-2">
                                        <a href="gerer_vols.php?action=modifier&id=<?php echo $vol_id; ?>" class="btn btn-primary">
                                            <i class="bi bi-pencil"></i> Modifier
                                        </a>
                                        
                                        <a href="gerer_vols.php?action=supprimer&id=<?php echo $vol_id; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce vol ?');">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">Aucune information disponible pour ce vol.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation de la date de retour (doit être après la date de départ)
        document.addEventListener('DOMContentLoaded', function() {
            const dateDepart = document.getElementById('date_depart');
            const dateRetour = document.getElementById('date_retour');
            
            if (dateDepart && dateRetour) {
                dateDepart.addEventListener('change', function() {
                    dateRetour.min = dateDepart.value;
                    if (dateRetour.value && dateRetour.value < dateDepart.value) {
                        dateRetour.value = dateDepart.value;
                    }
                });
                
                // Initialiser la date minimale de retour
                if (dateDepart.value) {
                    dateRetour.min = dateDepart.value;
                }
            }
        });
    </script>
</body>

</html>
