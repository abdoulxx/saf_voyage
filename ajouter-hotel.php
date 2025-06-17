<?php
session_start();
require_once 'config/config.php';

// Vérifier si l'ID du vol est fourni
if (!isset($_GET['vol_id']) || empty($_GET['vol_id'])) {
    header("Location: liste-vols.php");
    exit;
}

$vol_id = $_GET['vol_id'];

// Récupérer les informations du vol
try {
    $query = "SELECT * FROM vols WHERE id = :vol_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vol_id', $vol_id);
    $stmt->execute();
    $vol = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vol) {
        header("Location: liste-vols.php");
        exit;
    }
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération du vol: " . $e->getMessage();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les données du formulaire
        $nom = $_POST['nom'];
        $localisation = $_POST['localisation'];
        $description = $_POST['description'];
        $prix_nuit = $_POST['prix_nuit'];
        
        // Traitement de l'image
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $new_filename = strtolower(str_replace(' ', '_', $nom)) . '.' . $ext;
                $upload_path = 'assets/images/' . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image = $new_filename;
                } else {
                    throw new Exception("Erreur lors du téléchargement de l'image.");
                }
            } else {
                throw new Exception("Format d'image non autorisé. Utilisez JPG, JPEG, PNG ou GIF.");
            }
        } else {
            // Image par défaut si aucune n'est fournie
            $image = 'hotel_default.jpg';
        }
        
        // Insérer les données dans la base de données
        $query = "INSERT INTO hotels (nom, localisation, description, prix_nuit, image, vol_id) 
                  VALUES (:nom, :localisation, :description, :prix_nuit, :image, :vol_id)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':localisation', $localisation);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':prix_nuit', $prix_nuit);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':vol_id', $vol_id);
        
        $stmt->execute();
        
        $success_message = "L'hôtel a été ajouté avec succès!";
        
        // Rediriger vers la même page pour ajouter un autre hôtel ou terminer
        if (isset($_POST['add_another']) && $_POST['add_another'] == 1) {
            header("Location: ajouter-hotel.php?vol_id=" . $vol_id . "&success=1");
            exit;
        } else {
            header("Location: liste-vols.php?success=1");
            exit;
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Récupérer les hôtels déjà associés à ce vol
try {
    $query = "SELECT * FROM hotels WHERE vol_id = :vol_id ORDER BY nom";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vol_id', $vol_id);
    $stmt->execute();
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des hôtels: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Hôtel - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .admin-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-primary {
            background-color: rgba(6, 19, 116, 0.8);
            border: none;
        }
        .btn-primary:hover {
            background-color: rgb(10, 31, 122);
        }
        .hotel-list {
            margin-top: 30px;
        }
        .hotel-card {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2 class="mb-4">Ajouter un Hôtel pour <?php echo htmlspecialchars($vol['destination']); ?></h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">L'hôtel a été ajouté avec succès!</div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="vol-info mb-4">
            <h4>Informations du Vol</h4>
            <p><strong>Destination:</strong> <?php echo htmlspecialchars($vol['destination']); ?></p>
            <p><strong>Départ:</strong> <?php echo htmlspecialchars($vol['ville_depart']); ?></p>
            <p><strong>Date de départ:</strong> <?php echo htmlspecialchars($vol['date_depart']); ?></p>
            <p><strong>Date de retour:</strong> <?php echo htmlspecialchars($vol['date_retour']); ?></p>
            <p><strong>Durée:</strong> <?php echo htmlspecialchars($vol['duree']); ?> jours</p>
            <p><strong>Prix:</strong> <?php echo number_format($vol['prix'], 0, ',', ' '); ?> FCFA</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nom">Nom de l'hôtel</label>
                <input type="text" class="form-control" id="nom" name="nom" required>
            </div>
            
            <div class="form-group">
                <label for="localisation">Localisation</label>
                <input type="text" class="form-control" id="localisation" name="localisation" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="prix_nuit">Prix par nuit (FCFA)</label>
                <input type="number" class="form-control" id="prix_nuit" name="prix_nuit" required>
            </div>
            
            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" class="form-control" id="image" name="image">
                <small class="form-text text-muted">L'image sera renommée avec le nom de l'hôtel.</small>
            </div>
            
            <div class="form-check mb-3">
                <input type="checkbox" class="form-check-input" id="add_another" name="add_another" value="1">
                <label class="form-check-label" for="add_another">Ajouter un autre hôtel après celui-ci</label>
            </div>
            
            <button type="submit" class="btn btn-primary">Ajouter l'hôtel</button>
        </form>
        
        <?php if (!empty($hotels)): ?>
            <div class="hotel-list">
                <h4>Hôtels déjà associés à ce vol</h4>
                
                <?php foreach ($hotels as $hotel): ?>
                    <div class="hotel-card">
                        <h5><?php echo htmlspecialchars($hotel['nom']); ?></h5>
                        <p><strong>Localisation:</strong> <?php echo htmlspecialchars($hotel['localisation']); ?></p>
                        <p><strong>Prix par nuit:</strong> <?php echo number_format($hotel['prix_nuit'], 0, ',', ' '); ?> FCFA</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary">Retour à l'accueil</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
