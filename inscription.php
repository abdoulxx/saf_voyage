<?php
// Traitement de l'inscription
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'config/config.php'; // Connexion à la base de données

    // Récupérer les données du formulaire
    $nom = $_POST['nom'] ?? null;
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $numero_telephone = $_POST['numero_telephone'] ?? null;
    $localisation = $_POST['localisation'] ?? null;

    // Validation de base
    if (empty($nom)) {
        $errors[] = "Le nom est requis.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse e-mail invalide.";
    }
    if (empty($numero_telephone)) {
        $errors[] = "Le numéro de téléphone est requis.";
    } else {
        // Ajouter le préfixe +225 si ce n'est pas déjà inclus
        if (strpos($numero_telephone, '225') !== 0) {
            $numero_telephone = '225' . $numero_telephone;
        }
    }
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Si aucune erreur, procéder à l'inscription
    if (empty($errors)) {
        // Hasher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insérer dans la table utilisateurs
        try {
            $db->beginTransaction();

            // Insérer l'utilisateur avec le numéro de téléphone
            $stmt = $db->prepare("INSERT INTO users (nom, email, mot_de_passe, localisation, numero_telephone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $email, $hashed_password, $localisation, $numero_telephone]);

            // Valider la transaction
            $db->commit();

            // Message de succès avant la redirection
            $success_message = "Inscription réussie. Vous serez redirigé vers la page de connexion.";

            // Attendre 3 secondes avant de rediriger
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                  </script>";
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $db->rollBack();
            $errors[] = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>

<!-- Affichage des erreurs -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include('includes/head.php'); ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - safvoyage</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="http://localhost/saf_voyage/assets/images/logo.jpg" type="image/x-icon">
    <link rel="stylesheet" href="http://localhost/saf_voyage/assets/css/footer.css">

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
            width: 100%;
            max-width: 900px;
        }

        .card-body {
            padding: 40px;
        }

        .img-fluid {
            max-height: 100%;
            object-fit: cover;
        }

        .text-center {
            font-size: 1rem;
            color: #333;
        }

        .btn-custom {
            background-color: rgb(10, 31, 122);
            color: white;
        }

        .btn-custom:hover {
            background-color: rgb(10, 31, 122);
        }

        .inscription-link {
            text-align: center;
            font-size: 1rem;
            margin-top: 20px;
        }

        .inscription-link a {
            color: rgb(10, 31, 122);
            /* Utilise la même couleur que le bouton */
            text-decoration: none;
        }

        .inscription-link a:hover {
            text-decoration: underline;
        }

        .inscription-button {
            display: flex;
            justify-content: center;
        }

        .inscription-button input {
            background-color: rgb(10, 31, 122);
            /* Utilise la couleur spécifiée */
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 1.1rem;
            border-radius: 5px;
        }

        .inscription-button input:hover {
            background-color: rgb(8, 25, 97);
            /* Une teinte plus foncée pour l'effet hover */
        }
    </style>
</head>

<body>

    <?php include('includes/navbar.php'); ?>


    <div class="container-wrapper">
        <div class="card shadow-lg">
            <div class="row g-0">
                <div class="col-lg-6 p-4 d-flex align-items-center">
                    <div class="card-body">
                        <h2 class="card-title text-center">Inscription</h2>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>utilisateurs
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <input type="text" name="nom" class="form-control" placeholder="Nom complet" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" name="localisation" class="form-control" placeholder="Localisation" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" name="email" class="form-control" placeholder="Adresse e-mail" required>
                            </div>
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text">+225</span>
                                    <input type="text" id="numero_telephone" name="numero_telephone" class="form-control" placeholder="Numéro de téléphone" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" name="confirm_password" class="form-control" placeholder="Confirmez le mot de passe" required>
                            </div>

                            <div class="d-grid gap-2 inscription-button">
                                <input type="submit" value="S'inscrire" class="btn btn-warning">
                            </div>

                        </form>

                        <div class="mt-3 inscription-link">
                            <p>Vous avez un compte ? <a href="/saf_voyage/login.php">Connectez-vous ici</a>.</p>
                        </div>

                    </div>
                </div>

                <!-- Colonne pour l'image -->
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="image-container" style="height: 100%; background-size: cover; background-position: center;">
                        <img src="assets/images/logo.jpg" alt="Image d'un artiste" class="img-fluid h-100 w-100 rounded-end">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h4>Contactez-nous</h4>
                    <p><i class="fa fa-map-marker" aria-hidden="true"></i> Adresse: Angre, Abidjan, Côte d'Ivoire</p>
                    <p><i class="fa fa-phone" aria-hidden="true"></i> Téléphone: +225 07 09 72 80 25 / 01 52 00 79 85</p>
                    <p><i class="fa fa-envelope" aria-hidden="true"></i> Email: regissono225@gmail.com</p>
                </div>
                <div class="col-md-4">
                    <h4>Méthodes de paiement</h4>
                    <ul class="footer-links">
                        <li><img src="assets/images/wave.png" alt="Méthode de paiement 1" class="payment-icon"></li>
                        <li><img src="assets/images/orange.png" alt="Méthode de paiement 2" class="payment-icon"></li>
                        <li><img src="assets/images/mtn.png" alt="Méthode de paiement 3" class="payment-icon"></li>
                        <li><img src="assets/images/moov.png" alt="Méthode de paiement 4" class="payment-icon"></li>
                        <li><img src="assets/images/visa.png" alt="Méthode de paiement 5" class="payment-icon"></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h4>Suivez-nous</h4>
                    <div class="social-icons">
                        <a href="https://web.facebook.com/RegisSono225"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.instagram.com/regissono/"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.tiktok.com/@regissono?lang=fr"><i class="fab fa-tiktok"></i></a>
                        <a href="https://www.youtube.com/@RegisSono"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom mt-3">
            <p>&copy; 2024 REGISONO. Tous droits réservés.</p>
        </div>
    </footer>
</body>

</html>