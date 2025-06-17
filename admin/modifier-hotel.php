<?php
session_start();
require 'config/config.php';

// Vérifier si un ID d'hôtel est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: liste_hotels.php');
    exit;
}

$hotel_id = $_GET['id'];

// Récupérer les informations de l'hôtel
try {
    $query = "SELECT * FROM hotels WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $hotel_id);
    $stmt->execute();
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hotel) {
        header('Location: liste_hotels.php?error=hotel_not_found');
        exit;
    }
    
    // Récupérer les informations du vol associé
    $query = "SELECT * FROM vols WHERE id = :vol_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vol_id', $hotel['vol_id']);
    $stmt->execute();
    $vol = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
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
        $image = $hotel['image']; // Garder l'image existante par défaut
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                // Créer le répertoire d'upload s'il n'existe pas
                $upload_dir = '../assets/images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = strtolower(str_replace(' ', '_', $nom)) . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    // Supprimer l'ancienne image si elle existe et n'est pas l'image par défaut
                    if (!empty($hotel['image']) && $hotel['image'] !== 'hotel_default.jpg' && $hotel['image'] !== 'default.jpg' && file_exists($upload_dir . $hotel['image'])) {
                        unlink($upload_dir . $hotel['image']);
                    }
                    
                    $image = $new_filename;
                } else {
                    throw new Exception("Erreur lors du téléchargement de l'image.");
                }
            } else {
                throw new Exception("Format d'image non autorisé. Utilisez JPG, JPEG, PNG ou GIF.");
            }
        }
        
        // Mettre à jour les données dans la base de données
        $query = "UPDATE hotels SET 
                  nom = :nom, 
                  localisation = :localisation, 
                  description = :description, 
                  prix_nuit = :prix_nuit, 
                  image = :image 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':localisation', $localisation);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':prix_nuit', $prix_nuit);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':id', $hotel_id);
        
        $stmt->execute();
        
        // Rediriger vers la liste des hôtels avec un message de succès
        header("Location: liste_hotels.php?success=1");
        exit;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<?php include '../admin/top-navbar.php'; ?>
<?php include '../admin/sidebar.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Hôtel - Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles.css">
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
        .current-image {
            max-width: 200px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .vol-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h2 class="mb-4">Modifier un Hôtel</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($vol) && $vol): ?>
        <div class="vol-info">
            <h5>Informations du Vol</h5>
            <p><strong>Destination:</strong> <?php echo htmlspecialchars($vol['destination']); ?></p>
            <p><strong>Départ:</strong> <?php echo htmlspecialchars($vol['ville_depart']); ?></p>
            <p><strong>Dates:</strong> Du <?php echo date('d/m/Y', strtotime($vol['date_depart'])); ?> au <?php echo date('d/m/Y', strtotime($vol['date_retour'])); ?></p>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nom">Nom de l'hôtel</label>
                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($hotel['nom']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="localisation">Localisation</label>
                <input type="text" class="form-control" id="localisation" name="localisation" value="<?php echo htmlspecialchars($hotel['localisation']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($hotel['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="prix_nuit">Prix par nuit (FCFA)</label>
                <input type="number" class="form-control" id="prix_nuit" name="prix_nuit" value="<?php echo htmlspecialchars($hotel['prix_nuit']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="image">Image actuelle</label>
                <?php if (!empty($hotel['image']) && file_exists("../assets/images/" . $hotel['image'])): ?>
                    <div>
                        <img src="../assets/images/<?php echo $hotel['image']; ?>" alt="<?php echo htmlspecialchars($hotel['nom']); ?>" class="current-image">
                    </div>
                <?php else: ?>
                    <div>
                        <img src="../assets/images/hotel_default.jpg" alt="Image par défaut" class="current-image">
                    </div>
                <?php endif; ?>
                
                <label for="image">Nouvelle image (laissez vide pour conserver l'image actuelle)</label>
                <input type="file" class="form-control" id="image" name="image">
                <small class="form-text text-muted">L'image sera renommée avec le nom de l'hôtel et un timestamp.</small>
            </div>
            
            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="liste_hotels.php" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
