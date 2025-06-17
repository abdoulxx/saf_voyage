<?php

require 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $nom = htmlspecialchars($_POST['nom']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Validation des champs
    if (empty($nom) || empty($email) || empty($message)) {
        $error = "Tous les champs sont requis.";
    } else {
        // Insérer les données dans la table des messages
        try {
            $sql = "INSERT INTO messages_contact (nom, email, message) 
                    VALUES (:nom, :email, :message)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':email' => $email,
                ':message' => $message
            ]);

            $success = "Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.";
        } catch (PDOException $e) {
            $error = "Erreur lors de l'envoi du message : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<?php include('includes/head.php'); ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nous Contacter - Hôtel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        /* Style personnalisé pour le formulaire de contact */
        .contact-section {
            margin-top: 50px;
            padding: 30px;
            background-color: #f8f9fa;
        }

        .contact-section h2 {
            text-align: center;
            color: #0043ea;
            margin-bottom: 20px;
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-label {
            color:rgb(5, 21, 63);
        }

        .btn-primary {
            background-color: #0043ea;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0033cc;
        }

        .alert {
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include('includes/navbar.php'); ?>
    <!-- Formulaire de contact -->
    <section class="contact-section">
        <h2>Nous Contacter</h2>

        <!-- Afficher les messages d'erreur ou de succès -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="contact-form">
            <form method="POST" action="contact.php">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100">Envoyer</button>
            </form>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include('includes/footer.php'); ?>

</html>
