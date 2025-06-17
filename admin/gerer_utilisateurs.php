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
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = [];
$error = '';
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement du formulaire d'ajout/modification
    if (isset($_POST['save_user'])) {
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $numero_telephone = trim($_POST['numero_telephone']);
        $localisation = trim($_POST['localisation']);
        $role = $_POST['role'];
        $mot_de_passe = isset($_POST['mot_de_passe']) ? trim($_POST['mot_de_passe']) : '';
        
        // Validation des champs
        if (empty($nom) || empty($email) || empty($numero_telephone)) {
            $error = "Veuillez remplir tous les champs obligatoires.";
        } else {
            try {
                // Vérifier si l'email existe déjà (sauf pour la modification du même utilisateur)
                $check_query = "SELECT id FROM users WHERE email = :email AND id != :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(':email', $email);
                $check_stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $error = "Cet email est déjà utilisé par un autre utilisateur.";
                } else {
                    // Ajout ou modification d'un utilisateur
                    if ($action === 'ajouter') {
                        // Vérifier que le mot de passe est fourni pour un nouvel utilisateur
                        if (empty($mot_de_passe)) {
                            $error = "Le mot de passe est obligatoire pour un nouvel utilisateur.";
                        } else {
                            // Hachage du mot de passe
                            $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                            
                            // Insertion d'un nouvel utilisateur
                            $query = "INSERT INTO users (nom, email, mot_de_passe, role, localisation, numero_telephone, created_at) 
                                      VALUES (:nom, :email, :mot_de_passe, :role, :localisation, :numero_telephone, NOW())";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':nom', $nom);
                            $stmt->bindParam(':email', $email);
                            $stmt->bindParam(':mot_de_passe', $hashed_password);
                            $stmt->bindParam(':role', $role);
                            $stmt->bindParam(':localisation', $localisation);
                            $stmt->bindParam(':numero_telephone', $numero_telephone);
                            $stmt->execute();
                            
                            $success = "L'utilisateur a été ajouté avec succès.";
                            header("Location: liste_utilisateurs.php?success=" . urlencode($success));
                            exit;
                        }
                    } else {
                        // Modification d'un utilisateur existant
                        if (!empty($mot_de_passe)) {
                            // Si un nouveau mot de passe est fourni, le hacher
                            $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                            
                            $query = "UPDATE users SET nom = :nom, email = :email, mot_de_passe = :mot_de_passe, 
                                      role = :role, localisation = :localisation, numero_telephone = :numero_telephone 
                                      WHERE id = :id";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':mot_de_passe', $hashed_password);
                        } else {
                            // Sinon, ne pas modifier le mot de passe
                            $query = "UPDATE users SET nom = :nom, email = :email, role = :role, 
                                      localisation = :localisation, numero_telephone = :numero_telephone 
                                      WHERE id = :id";
                            $stmt = $db->prepare($query);
                        }
                        
                        $stmt->bindParam(':nom', $nom);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':role', $role);
                        $stmt->bindParam(':localisation', $localisation);
                        $stmt->bindParam(':numero_telephone', $numero_telephone);
                        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
                        $stmt->execute();
                        
                        $success = "L'utilisateur a été modifié avec succès.";
                        header("Location: liste_utilisateurs.php?success=" . urlencode($success));
                        exit;
                    }
                }
            } catch (PDOException $e) {
                $error = "Erreur lors de l'enregistrement: " . $e->getMessage();
            }
        }
    }
}

// Suppression d'un utilisateur
if ($action === 'supprimer' && $user_id > 0) {
    try {
        // Vérifier que l'utilisateur n'est pas en train de se supprimer lui-même
        if ($user_id === $_SESSION['admin_id']) {
            $error = "Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = "L'utilisateur a été supprimé avec succès.";
            header("Location: liste_utilisateurs.php?success=" . urlencode($success));
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

// Récupérer les informations de l'utilisateur pour modification ou visualisation
if (($action === 'modifier' || $action === 'voir') && $user_id > 0) {
    try {
        $query = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $error = "Utilisateur introuvable.";
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
        $page_title = "Ajouter un utilisateur";
        break;
    case 'modifier':
        $page_title = "Modifier l'utilisateur";
        break;
    case 'voir':
        $page_title = "Détails de l'utilisateur";
        break;
    default:
        header("Location: liste_utilisateurs.php");
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
</head>

<body>
    <?php include '../admin/top-navbar.php'; ?>
    <?php include '../admin/sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800"><?php echo $page_title; ?></h1>
                <a href="liste_utilisateurs.php" class="btn btn-secondary">
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
                    <?php if ($action === 'voir'): ?>
                        <!-- Affichage des détails de l'utilisateur -->
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-4">Informations personnelles</h5>
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($user['nom']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Téléphone:</strong> <?php echo htmlspecialchars($user['numero_telephone']); ?></p>
                                <p><strong>Localisation:</strong> <?php echo htmlspecialchars($user['localisation'] ?? 'Non spécifiée'); ?></p>
                                <p><strong>Rôle:</strong> <?php echo ucfirst($user['role']); ?></p>
                                <p><strong>Date d'inscription:</strong> <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-4">Activité</h5>
                                <?php
                                // Récupérer les réservations de l'utilisateur
                                try {
                                    $query = "SELECT COUNT(*) as total_reservations FROM reservations WHERE user_id = :user_id";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $total_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['total_reservations'];
                                } catch (PDOException $e) {
                                    $total_reservations = 0;
                                }
                                ?>
                                <p><strong>Nombre de réservations:</strong> <?php echo $total_reservations; ?></p>
                                
                                <?php
                                // Récupérer le montant total des paiements
                                try {
                                    $query = "SELECT SUM(prix_total) as total_payments FROM reservations WHERE user_id = :user_id AND statut_paiement = 'paye'";
                                    $stmt = $db->prepare($query);
                                    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                                    $stmt->execute();
                                    $total_payments = $stmt->fetch(PDO::FETCH_ASSOC)['total_payments'];
                                    if ($total_payments === null) $total_payments = 0;
                                } catch (PDOException $e) {
                                    $total_payments = 0;
                                }
                                ?>
                                <p><strong>Montant total des paiements:</strong> <?php echo number_format($total_payments, 0, ',', ' '); ?> FCFA</p>
                                
                                <div class="mt-4">
                                    <a href="gerer_utilisateurs.php?action=modifier&id=<?php echo $user_id; ?>" class="btn btn-primary">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </a>
                                    <?php if ($user_id != $_SESSION['admin_id']): ?>
                                        <a href="gerer_utilisateurs.php?action=supprimer&id=<?php echo $user_id; ?>" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Formulaire d'ajout/modification -->
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="numero_telephone" class="form-label">Numéro de téléphone <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="numero_telephone" name="numero_telephone" value="<?php echo htmlspecialchars($user['numero_telephone'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="localisation" class="form-label">Localisation</label>
                                    <input type="text" class="form-control" id="localisation" name="localisation" value="<?php echo htmlspecialchars($user['localisation'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="client" <?php echo (isset($user['role']) && $user['role'] === 'client') ? 'selected' : ''; ?>>Client</option>
                                        <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="mot_de_passe" class="form-label">
                                        <?php echo $action === 'ajouter' ? 'Mot de passe <span class="text-danger">*</span>' : 'Nouveau mot de passe <small>(laisser vide pour ne pas modifier)</small>'; ?>
                                    </label>
                                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" <?php echo $action === 'ajouter' ? 'required' : ''; ?>>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" name="save_user" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer
                                </button>
                                <a href="liste_utilisateurs.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
