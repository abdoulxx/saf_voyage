<?php
session_start();
include('../config/config.php');

// Initialisation des variables pour gérer les erreurs
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $email = htmlspecialchars($_POST['email']);
    $password = htmlspecialchars($_POST['password']);

    // Valider que tous les champs sont remplis
    if (empty($email) || empty($password)) {
        $error_message = "Tous les champs sont requis.";
    } else {
        // Rechercher l'utilisateur dans la base de données
        try {
            $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérifier si l'utilisateur existe et si le mot de passe est correct
            if ($admin && password_verify($password, $admin['password'])) {
                // Créer la session pour l'utilisateur connecté
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['nom'];

                // Mettre à jour la dernière connexion et la localisation
                $last_login = date('Y-m-d H:i:s');  // Date et heure actuelles
                $location = $_SERVER['REMOTE_ADDR'];  // Adresse IP de l'utilisateur

                // Redirection vers le tableau de bord de l'administrateur
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = "Email ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la connexion : " . $e->getMessage();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur - Regisono</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet"> <!-- Bootstrap Icons -->
    <style>
        body {
            background-image: url('back.png');
            /* Remplacez avec le chemin de votre image */
            background-size: cover;
            background-position: center;
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: #ffffff;
            /* Fond totalement blanc */
            /* Léger fond blanc semi-transparent */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
            /* Centrer le logo */
        }

        .btn-custom {
            background-color: rgb(10, 31, 122);
            color: #fff;
            border-radius: 30px;
            transition: background-color 0.3s ease;
        }

        .btn-custom:hover {
            background-color: rgb(10, 31, 122);
        }

        .alert {
            font-size: 1rem;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }

        /* Style pour le logo */
        .login-logo {
            width: 150px;
            /* Ajustez la taille du logo */
        }
    </style>
</head>

<body>

    <div class="login-container">
        <!-- Ajouter le logo -->
        <img src="logo.jpg" alt="Logo Regisono" class="login-logo"> <!-- Remplacez par le chemin de votre logo -->

        <h2>Connexion Admin <i class="bi bi-lock-fill"></i> </h2>

        <!-- Afficher un message d'erreur s'il existe -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php">
            <div class="mb-3">
                <input type="email" name="email" class="form-control" placeholder="Votre email" required>
            </div>
            <div class="mb-3">
                <input type="password" name="password" class="form-control" placeholder="Votre mot de passe" required>
            </div>
            <button type="submit" class="btn btn-custom w-100">Se connecter</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>