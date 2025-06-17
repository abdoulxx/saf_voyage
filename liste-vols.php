<?php
session_start();
require 'config/config.php';

// Récupérer tous les vols
try {
    $query = "SELECT v.*, COUNT(h.id) as nb_hotels 
              FROM vols v 
              LEFT JOIN hotels h ON v.id = h.vol_id 
              GROUP BY v.id 
              ORDER BY v.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $vols = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des vols: " . $e->getMessage();
}

// Supprimer un vol
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    try {
        $vol_id = $_GET['delete'];
        
        // Supprimer le vol (les hôtels seront supprimés automatiquement grâce à la contrainte ON DELETE CASCADE)
        $query = "DELETE FROM vols WHERE id = :vol_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':vol_id', $vol_id);
        $stmt->execute();
        
        // Rediriger pour éviter les soumissions multiples
        header("Location: liste-vols.php?deleted=1");
        exit;
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la suppression du vol: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Vols - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .btn-primary {
            background-color: rgba(6, 19, 116, 0.8);
            border: none;
        }
        .btn-primary:hover {
            background-color: rgb(10, 31, 122);
        }
        .vol-card {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .vol-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Liste des Vols</h2>
            <a href="ajouter-vol.php" class="btn btn-primary">Ajouter un vol</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Opération réussie!</div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Le vol a été supprimé avec succès!</div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (empty($vols)): ?>
            <div class="alert alert-info">Aucun vol n'a été ajouté pour le moment.</div>
        <?php else: ?>
            <?php foreach ($vols as $vol): ?>
                <div class="vol-card">
                    <div class="row">
                        <div class="col-md-3">
                            <?php if (!empty($vol['image']) && file_exists("../assets/images/" . $vol['image'])): ?>
                                <img src="../assets/images/<?php echo $vol['image']; ?>" alt="<?php echo htmlspecialchars($vol['destination']); ?>" class="img-fluid rounded">
                            <?php else: ?>
                                <img src="../assets/images/default.jpg" alt="Image par défaut" class="img-fluid rounded">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <h4><?php echo htmlspecialchars($vol['ville_depart']); ?> - <?php echo htmlspecialchars($vol['destination']); ?> - <?php echo $vol['duree']; ?> jours</h4>
                            <p><?php echo nl2br(htmlspecialchars($vol['description'])); ?></p>
                            <p><strong>Départ:</strong> <?php echo date('d/m/Y', strtotime($vol['date_depart'])); ?> | <strong>Retour:</strong> <?php echo date('d/m/Y', strtotime($vol['date_retour'])); ?></p>
                            <p><strong>Prix:</strong> <?php echo number_format($vol['prix'], 0, ',', ' '); ?> FCFA</p>
                            <p><strong>Hôtels associés:</strong> <?php echo $vol['nb_hotels']; ?></p>
                            
                            <div class="vol-actions">
                                <a href="ajouter-hotel.php?vol_id=<?php echo $vol['id']; ?>" class="btn btn-primary btn-sm">Gérer les hôtels</a>
                                <a href="modifier-vol.php?id=<?php echo $vol['id']; ?>" class="btn btn-warning btn-sm">Modifier</a>
                                <a href="liste-vols.php?delete=<?php echo $vol['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce vol et tous les hôtels associés?')">Supprimer</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="../index.php" class="btn btn-secondary">Retour au site</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
