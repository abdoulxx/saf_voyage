<?php
session_start();
require 'config/config.php';

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupérer les données du formulaire
        $destination = $_POST['destination'];
        $ville_depart = $_POST['ville_depart'];
        $description = $_POST['description'];
        $prix = $_POST['prix'];
        $date_depart = $_POST['date_depart'];
        $date_retour = $_POST['date_retour'];
        
        // Calculer la durée en jours
        $date1 = new DateTime($date_depart);
        $date2 = new DateTime($date_retour);
        $duree = $date2->diff($date1)->days;
        
        // Traitement de l'image
        $image = '';
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
                
                $new_filename = strtolower($destination) . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;
                
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
            $image = 'default.jpg';
        }
        
        // Insérer les données dans la base de données
        $query = "INSERT INTO vols (destination, ville_depart, description, prix, date_depart, date_retour, duree, image) 
                  VALUES (:destination, :ville_depart, :description, :prix, :date_depart, :date_retour, :duree, :image)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':destination', $destination);
        $stmt->bindParam(':ville_depart', $ville_depart);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':prix', $prix);
        $stmt->bindParam(':date_depart', $date_depart);
        $stmt->bindParam(':date_retour', $date_retour);
        $stmt->bindParam(':duree', $duree);
        $stmt->bindParam(':image', $image);
        
        $stmt->execute();
        
        // Récupérer l'ID du vol nouvellement inséré
        $vol_id = $db->lastInsertId();
        
        // Rediriger vers la page d'ajout d'hôtels avec l'ID du vol
        header("Location: ajouter-hotel.php?vol_id=" . $vol_id);
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
    <title>Ajouter un Vol - Administration</title>
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
    </style>
</head>
<body>
    <div class="admin-container">
        <h2 class="mb-4">Ajouter un Vol</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="destination">Destination</label>
                <input type="text" class="form-control" id="destination" name="destination" required>
            </div>
            
            <div class="form-group">
                <label for="ville_depart">Ville de départ</label>
                <input type="text" class="form-control" id="ville_depart" name="ville_depart" value="Abidjan" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="prix">Prix (FCFA)</label>
                <input type="number" class="form-control" id="prix" name="prix" required>
            </div>
            
            <div class="form-group">
                <label for="date_depart">Date de départ</label>
                <input type="date" class="form-control" id="date_depart" name="date_depart" required>
            </div>
            
            <div class="form-group">
                <label for="date_retour">Date de retour</label>
                <input type="date" class="form-control" id="date_retour" name="date_retour" required>
            </div>
            
            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" class="form-control" id="image" name="image">
                <small class="form-text text-muted">L'image sera renommée avec le nom de la destination.</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Ajouter le vol et continuer vers les hôtels</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
