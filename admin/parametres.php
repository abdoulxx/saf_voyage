<?php
session_start();
include('../config/config.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Initialiser les variables
$error = '';
$success = '';

// Récupérer les informations de l'administrateur connecté
try {
    $query = "SELECT * FROM admins WHERE id = :admin_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':admin_id', $_SESSION['admin_id'], PDO::PARAM_INT);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des informations: " . $e->getMessage();
    $admin = [];
}

// Traitement du formulaire d'ajout d'administrateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_admin'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $query = "SELECT COUNT(*) as count FROM admins WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                $error = "Cet email est déjà utilisé par un autre administrateur.";
            } else {
                // Hachage du mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertion du nouvel administrateur
                $query = "INSERT INTO admins (nom, prenom, email, password, role) VALUES (:nom, :prenom, :email, :password, 'admin')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':nom', $nom);
                $stmt->bindParam(':prenom', $prenom);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->execute();
                
                $success = "Le nouvel administrateur a été ajouté avec succès.";
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout de l'administrateur: " . $e->getMessage();
        }
    }
}

// Traitement du formulaire de modification du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_profil'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $password_actuel = $_POST['password_actuel'];
    $nouveau_password = $_POST['nouveau_password'];
    $confirm_nouveau_password = $_POST['confirm_nouveau_password'];
    
    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email)) {
        $error = "Le nom et l'email sont obligatoires.";
    } elseif (!empty($nouveau_password) && empty($password_actuel)) {
        $error = "Veuillez saisir votre mot de passe actuel pour le modifier.";
    } elseif (!empty($nouveau_password) && $nouveau_password !== $confirm_nouveau_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (!empty($nouveau_password) && strlen($nouveau_password) < 8) {
        $error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
    } else {
        try {
            // Vérifier si l'email existe déjà pour un autre administrateur
            $query = "SELECT COUNT(*) as count FROM admins WHERE email = :email AND id != :admin_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':admin_id', $_SESSION['admin_id'], PDO::PARAM_INT);
            $stmt->execute();
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                $error = "Cet email est déjà utilisé par un autre administrateur.";
            } else {
                // Si un nouveau mot de passe est fourni, vérifier l'ancien
                if (!empty($nouveau_password)) {
                    // Vérifier le mot de passe actuel
                    if (!password_verify($password_actuel, $admin['password'])) {
                        $error = "Le mot de passe actuel est incorrect.";
                    } else {
                        // Hachage du nouveau mot de passe
                        $hashed_password = password_hash($nouveau_password, PASSWORD_DEFAULT);
                        
                        // Mise à jour du profil avec le nouveau mot de passe
                        $query = "UPDATE admins SET nom = :nom, prenom = :prenom, email = :email, password = :password WHERE id = :admin_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':nom', $nom);
                        $stmt->bindParam(':prenom', $prenom);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':password', $hashed_password);
                        $stmt->bindParam(':admin_id', $_SESSION['admin_id'], PDO::PARAM_INT);
                        $stmt->execute();
                        
                        $success = "Votre profil a été mis à jour avec succès.";
                        
                        // Mettre à jour les informations de session
                        $_SESSION['admin_nom'] = $nom;
                        $_SESSION['admin_email'] = $email;
                        
                        // Rafraîchir les informations de l'administrateur
                        $query = "SELECT * FROM admins WHERE id = :admin_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':admin_id', $_SESSION['admin_id'], PDO::PARAM_INT);
                        $stmt->execute();
                        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                } else {
                    // Mise à jour du profil sans changer le mot de passe
                    $query = "UPDATE admins SET nom = :nom, prenom = :prenom, email = :email WHERE id = :admin_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nom', $nom);
                    $stmt->bindParam(':prenom', $prenom);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':admin_id', $_SESSION['admin_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $success = "Votre profil a été mis à jour avec succès.";
                    
                    // Mettre à jour les informations de session
                    $_SESSION['admin_nom'] = $nom;
                    $_SESSION['admin_email'] = $email;
                    
                    // Rafraîchir les informations de l'administrateur
                    $query = "SELECT * FROM admins WHERE id = :admin_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':admin_id', $_SESSION['admin_id'], PDO::PARAM_INT);
                    $stmt->execute();
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
        }
    }
}

// Récupérer la liste des administrateurs
try {
    $query = "SELECT id, nom, prenom, email, created_at FROM admins ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des administrateurs: " . $e->getMessage();
    $admins = [];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: #4e73df;
            border-bottom: 2px solid #4e73df;
            background-color: transparent;
        }
        
        .nav-tabs .nav-link:hover {
            color: #4e73df;
            border-color: transparent;
        }
        
        .admin-list-item {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .admin-list-item:hover {
            background-color: #f8f9fa;
            border-left-color: #4e73df;
        }
        
        .admin-list-item.current-user {
            background-color: #e8f4ff;
            border-left-color: #4e73df;
        }
    </style>
</head>

<body>
    <?php include '../admin/top-navbar.php'; ?>
    <?php include '../admin/sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Paramètres</h1>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <ul class="nav nav-tabs card-header-tabs" id="paramsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">
                                <i class="bi bi-person"></i> Mon profil
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab" aria-controls="admins" aria-selected="false">
                                <i class="bi bi-people"></i> Administrateurs
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="add-admin-tab" data-bs-toggle="tab" data-bs-target="#add-admin" type="button" role="tab" aria-controls="add-admin" aria-selected="false">
                                <i class="bi bi-person-plus"></i> Ajouter un administrateur
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="paramsTabsContent">
                        <!-- Onglet Mon Profil -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <h5 class="mb-4">Modifier mon profil</h5>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($admin['nom']) ? htmlspecialchars($admin['nom']) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="prenom" class="form-label">Prénom</label>
                                            <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo isset($admin['prenom']) ? htmlspecialchars($admin['prenom']) : ''; ?>" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($admin['email']) ? htmlspecialchars($admin['email']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password_actuel" class="form-label">Mot de passe actuel</label>
                                            <input type="password" class="form-control" id="password_actuel" name="password_actuel">
                                            <div class="form-text">Requis uniquement si vous souhaitez changer votre mot de passe</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="nouveau_password" class="form-label">Nouveau mot de passe</label>
                                            <input type="password" class="form-control" id="nouveau_password" name="nouveau_password">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_nouveau_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                            <input type="password" class="form-control" id="confirm_nouveau_password" name="confirm_nouveau_password">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="modifier_profil" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                        
                        <!-- Onglet Administrateurs -->
                        <div class="tab-pane fade" id="admins" role="tabpanel" aria-labelledby="admins-tab">
                            <h5 class="mb-4">Liste des administrateurs</h5>
                            <div class="list-group">
                                <?php if (empty($admins)): ?>
                                    <div class="alert alert-info">Aucun administrateur trouvé.</div>
                                <?php else: ?>
                                    <?php foreach ($admins as $admin_item): ?>
                                        <div class="list-group-item admin-list-item <?php echo $admin_item['id'] == $_SESSION['admin_id'] ? 'current-user' : ''; ?>">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($admin_item['nom']); ?> <?php echo htmlspecialchars($admin_item['prenom']); ?></h6>
                                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($admin_item['email']); ?></p>
                                                    <small class="text-muted">Inscrit le <?php echo date('d/m/Y', strtotime($admin_item['created_at'])); ?></small>
                                                </div>
                                                <?php if ($admin_item['id'] == $_SESSION['admin_id']): ?>
                                                    <span class="badge bg-primary">Vous</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Onglet Ajouter un administrateur -->
                        <div class="tab-pane fade" id="add-admin" role="tabpanel" aria-labelledby="add-admin-tab">
                            <h5 class="mb-4">Ajouter un nouvel administrateur</h5>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="nom" name="nom" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="prenom" class="form-label">Prénom</label>
                                            <input type="text" class="form-control" id="prenom" name="prenom" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Mot de passe</label>
                                            <input type="password" class="form-control" id="password" name="password" required>
                                            <div class="form-text">Le mot de passe doit contenir au moins 8 caractères</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" name="ajouter_admin" class="btn btn-success">
                                    <i class="bi bi-person-plus"></i> Ajouter l'administrateur
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
