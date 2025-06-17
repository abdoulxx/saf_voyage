<?php
session_start();
require 'config/config.php'; // Fichier de connexion PDO

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur connecté
$query_user = "SELECT * FROM users WHERE id = :user_id";
$stmt_user = $db->prepare($query_user);
$stmt_user->execute([':user_id' => $user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// Initialisation des variables
$nom = $email = $numero_telephone = $localisation = '';
$error = $success = '';

// Pré-remplir les champs avec les données actuelles de l'utilisateur
if ($user) {
    $nom = $user['nom'];
    $email = $user['email'];
    $numero_telephone = $user['numero_telephone'];
    $localisation = $user['localisation'] ?? '';
}

// Récupérer les réservations de l'utilisateur
try {
    $query_reservations = "SELECT r.*, v.destination, v.date_depart, v.date_retour, 
                          h.nom as hotel_nom
                          FROM reservations r
                          LEFT JOIN vols v ON r.vol_id = v.id
                          LEFT JOIN hotels h ON r.hotel_id = h.id
                          WHERE r.user_id = :user_id
                          ORDER BY r.date_reservation DESC";
    $stmt_reservations = $db->prepare($query_reservations);
    $stmt_reservations->execute([':user_id' => $user_id]);
    $reservations = $stmt_reservations->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des réservations: " . $e->getMessage();
    $reservations = [];
}

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données soumises par le formulaire
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $numero_telephone = $_POST['numero_telephone'];
    $localisation = $_POST['localisation'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $current_password = $_POST['current_password'];

    // Validation des données
    if (empty($nom) || empty($email) || empty($numero_telephone)) {
        $error = "Les champs Nom, Email et Numéro de téléphone sont obligatoires.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (!empty($new_password) && strlen($new_password) < 8) {
        $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } else {
        // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
        $query_check_email = "SELECT COUNT(*) as count FROM users WHERE email = :email AND id != :user_id";
        $stmt_check_email = $db->prepare($query_check_email);
        $stmt_check_email->execute([':email' => $email, ':user_id' => $user_id]);
        $email_count = $stmt_check_email->fetch(PDO::FETCH_ASSOC)['count'];

        if ($email_count > 0) {
            $error = "Cet email est déjà utilisé par un autre compte.";
        } else {
            // Vérifier que le mot de passe actuel est correct si un nouveau mot de passe est fourni
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = "Veuillez saisir votre mot de passe actuel pour le modifier.";
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = "Le mot de passe actuel est incorrect.";
                }
            }

            // Si aucune erreur, procéder à la mise à jour des données
            if (empty($error)) {
                try {
                    // Préparer la requête de mise à jour
                    if (!empty($new_password)) {
                        // Mise à jour avec nouveau mot de passe
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $query_update = "UPDATE users SET 
                                        nom = :nom, 
                                        email = :email, 
                                        numero_telephone = :numero_telephone, 
                                        localisation = :localisation, 
                                        password = :password 
                                        WHERE id = :user_id";
                        $params = [
                            ':nom' => $nom,
                            ':email' => $email,
                            ':numero_telephone' => $numero_telephone,
                            ':localisation' => $localisation,
                            ':password' => $hashed_password,
                            ':user_id' => $user_id
                        ];
                    } else {
                        // Mise à jour sans changer le mot de passe
                        $query_update = "UPDATE users SET 
                                        nom = :nom, 
                                        email = :email, 
                                        numero_telephone = :numero_telephone, 
                                        localisation = :localisation 
                                        WHERE id = :user_id";
                        $params = [
                            ':nom' => $nom,
                            ':email' => $email,
                            ':numero_telephone' => $numero_telephone,
                            ':localisation' => $localisation,
                            ':user_id' => $user_id
                        ];
                    }

                    // Exécuter la requête
                    $stmt_update = $db->prepare($query_update);
                    $stmt_update->execute($params);

                    $success = "Vos informations ont été mises à jour avec succès.";
                    
                    // Mettre à jour les informations de session
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_email'] = $email;
                    
                    // Rafraîchir les informations de l'utilisateur
                    $stmt_user->execute([':user_id' => $user_id]);
                    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $error = "Erreur lors de la mise à jour: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include('includes/head.php'); ?>
    <title>Mon Profil - Saf Voyage</title>
    <style>
        .profile-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #6c757d;
            margin-right: 20px;
        }
        
        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.5rem 1rem;
            margin-right: 1rem;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-bottom: 2px solid #0d6efd;
            background-color: transparent;
        }
        
        .reservation-card {
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .reservation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4em 0.8em;
        }
    </style>
</head>
<body>
    <?php include('includes/navbar.php'); ?>

    <div class="container my-5">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="bi bi-person"></i>
                </div>
                <div>
                    <h2><?php echo htmlspecialchars($nom); ?></h2>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($email); ?></p>
                    <p class="text-muted"><?php echo htmlspecialchars($numero_telephone); ?></p>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                        <i class="bi bi-person-gear"></i> Modifier mon profil
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reservations-tab" data-bs-toggle="tab" data-bs-target="#reservations" type="button" role="tab" aria-controls="reservations" aria-selected="false">
                        <i class="bi bi-calendar-check"></i> Mes réservations
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="profileTabsContent">
                <!-- Onglet Profil -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                    <form method="POST" action="profil.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom complet</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($nom); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="numero_telephone" class="form-label">Numéro de téléphone</label>
                                <input type="text" class="form-control" id="numero_telephone" name="numero_telephone" value="<?php echo htmlspecialchars($numero_telephone); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="localisation" class="form-label">Localisation</label>
                                <input type="text" class="form-control" id="localisation" name="localisation" value="<?php echo htmlspecialchars($localisation); ?>" placeholder="Ville, Pays">
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <h5>Changer de mot de passe</h5>
                        <p class="text-muted small">Laissez ces champs vides si vous ne souhaitez pas modifier votre mot de passe.</p>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">8 caractères minimum</div>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Onglet Réservations -->
                <div class="tab-pane fade" id="reservations" role="tabpanel" aria-labelledby="reservations-tab">
                    <?php if (empty($reservations)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Vous n'avez pas encore effectué de réservation.
                            <a href="index.php" class="alert-link">Découvrez nos destinations</a> et planifiez votre prochain voyage !
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($reservations as $reservation): ?>
                                <div class="col-md-6">
                                    <div class="card reservation-card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0"><?php echo ucfirst(htmlspecialchars($reservation['destination'])); ?></h5>
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
                                            <span class="badge bg-<?php echo $status_class; ?> status-badge"><?php echo $status_text; ?></span>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-2">
                                                <i class="bi bi-calendar"></i> <strong>Dates:</strong> 
                                                <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?> - 
                                                <?php echo date('d/m/Y', strtotime($reservation['date_retour'])); ?>
                                            </div>
                                            
                                            <?php if (!empty($reservation['hotel_nom'])): ?>
                                                <div class="mb-2">
                                                    <i class="bi bi-building"></i> <strong>Hôtel:</strong> 
                                                    <?php echo htmlspecialchars($reservation['hotel_nom']); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-2">
                                                <i class="bi bi-cash"></i> <strong>Montant:</strong> 
                                                <?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA
                                            </div>
                                            
                                            <div class="mb-2">
                                                <i class="bi bi-credit-card"></i> <strong>Méthode de paiement:</strong> 
                                                <?php echo $reservation['methode_paiement'] === 'reception' ? 'Paiement à la réception' : 'Paiement en ligne'; ?>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <i class="bi bi-clock-history"></i> <strong>Réservé le:</strong> 
                                                <?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex justify-content-between">
                                                <a href="reservation-details.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> Détails
                                                </a>
                                                
                                                <?php if ($reservation['statut_paiement'] === 'paye'): ?>
                                                    <a href="facture-pdf.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-success" target="_blank">
                                                        <i class="bi bi-file-pdf"></i> Télécharger la facture
                                                    </a>
                                                <?php elseif ($reservation['statut_paiement'] === 'en_attente' || $reservation['statut_paiement'] === 'non_paye'): ?>
                                                    <a href="paiement.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                        <i class="bi bi-credit-card"></i> Payer maintenant
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    <script src="[https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>](https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>)
</body>
</html>