<?php
// Démarrer la session
session_start();

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'config/config.php'; // Connexion à la base de données

    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];

    // Validation des champs
    if (empty($email) || empty($mot_de_passe)) {
        $error = "Tous les champs sont requis.";
    } else {
        // Vérification de l'utilisateur dans la base de données
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérifier si le mot de passe est correct
            if (password_verify($mot_de_passe, $user['mot_de_passe'])) {
                // Connexion réussie, démarrage de la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];

                // Rediriger vers la page d'accueil ou tableau de bord
                header('Location: index.php');
                exit(); // S'assurer que le code s'arrête après la redirection
            } else {
                $error = "Mot de passe incorrect.";
            }
        } else {
            $error = "Aucun utilisateur trouvé avec cet email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="http://localhost/regisono/assets/css/footer.css">
    <style>
        body {
            background-color: #f5f5f5;
        }

        .container-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .card {
            border-radius: 15px;
            display: flex;
            justify-content: center;
            overflow: hidden;
        }

        .img-fluid {
            max-height: 100%;
            object-fit: cover;
        }

        .navbar-nav {
            margin-left: auto;
        }

        /* Styles pour le sidenav (menu) si utilisé */
        .sidenav {
            height: 100%;
            width: 250px;
            position: fixed;
            z-index: 1;
            top: 0;
            left: -250px;
            background-color: #e8e8e8;
            padding-top: 60px;
            transition: left 0.5s ease;
        }

        .sidenav a {
            padding: 8px 8px 8px 32px;
            text-decoration: none;
            font-size: 25px;
            color: #818181;
            display: block;
            transition: 0.3s;
        }

        .sidenav a:hover {
            color: #ffcc33;
        }

        .sidenav ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .sidenav.active {
            left: 0;
        }

        .sidenav .close {
            position: absolute;
            top: 0;
            right: 25px;
            font-size: 36px;
        }

        .burger-icon span {
            display: block;
            width: 35px;
            height: 5px;
            background-color: #ffcc33;
            margin: 6px 0;
        }

        /* Mise en page du formulaire et de l'image */
        .card-body {
            padding: 40px;
        }

        .image-container {
            flex: 1;
            background-image: url('assets/images/logo.jpg');
            background-size: cover;
            background-position: center;
            height: 100%;
        }

        .form-label {
            font-size: 1.1rem;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .btn-custom {
            background-color: rgb(10, 31, 122);
            color: white;
        }

        .btn-custom:hover {
            background-color: rgb(10, 31, 122);
        }

        .text-center {
            font-size: 1rem;
            color: #333;
        }

        @media (max-width: 992px) {
            .card {
                flex-direction: column;
            }

            .image-container {
                display: none;
            }
        }
    </style>

    <script>
        var sidenav = document.getElementById("mySidenav");
        var openBtn = document.getElementById("openBtn");
        var closeBtn = document.getElementById("closeBtn");

        openBtn.onclick = openNav;
        closeBtn.onclick = closeNav;

        /* Fonction pour ouvrir le sidenav */
        function openNav() {
            sidenav.classList.add("active");
        }

        /* Fonction pour fermer le sidenav */
        function closeNav() {
            sidenav.classList.remove("active");
        }
    </script>
</head>

<body>
    <?php include('includes/navbar.php'); ?>


    <div class="container-wrapper">
        <div class="card shadow-lg" style="width: 100%; max-width: 900px;">
            <div class="row g-0">
                <div class="col-lg-6 p-4 d-flex align-items-center">
                    <div class="card-body">
                        <h2 class="card-title text-center">Connexion</h2>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse e-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                            </div>

                            <button type="submit" class="btn btn-custom w-100">Se connecter</button>
                        </form>

                        <div class="mt-3">
                            <p>Vous n'avez pas de compte ? <a href="/saf_voyage/inscription.php">Inscrivez-vous ici</a>.</p>
                        </div>
                    </div>
                </div>

                <!-- Colonne pour l'image -->
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="image-container"></div>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>