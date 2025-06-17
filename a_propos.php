<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include('includes/head.php'); ?>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À Propos de Saf Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: 'Poppins', sans-serif;
        }

        .about-section {
            padding: 50px 20px;
            background-color: #ffffff;
        }

        .about-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .about-header h1 {
            font-size: 2.5rem;
            color: #007bff;
            font-weight: bold;
        }

        .about-header p {
            color: #555;
            font-size: 1.1rem;
        }

        .team-card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-top: 20px;
            background-color: white;
            text-align: center;
            padding: 20px;
        }

        .team-card img {
            border-radius: 50%;
            width: 150px;
            height: 150px;
            object-fit: cover;
        }

        .team-card h4 {
            color: #007bff;
            margin-top: 15px;
            font-size: 1.5rem;
        }

        .team-card p {
            color: #555;
            font-size: 1rem;
        }

        .values {
            background-color: #f8f8f8;
            padding: 50px 20px;
        }

        .values h2 {
            text-align: center;
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 30px;
        }

        .value-item {
            margin-bottom: 30px;
        }

        .value-item h3 {
            color: #007bff;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .value-item p {
            color: #555;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>

    <?php include('includes/navbar.php'); ?>

    <!-- Section "À propos" -->
    <div class="about-section">
        <div class="container">
            <div class="about-header">
                <h1>À Propos de Saf Voyage</h1>
                <p>Nous sommes une agence de voyage passionnée par l'exploration du monde et la création d'expériences inoubliables pour nos clients.</p>
            </div>

            <div class="row">
                <!-- Notre équipe -->
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="assets/images/team-member1.jpg" alt="Membre de l'équipe">
                        <h4>Jean Dupont</h4>
                        <p>CEO & Fondateur</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="assets/images/team-member1.jpg" alt="Membre de l'équipe">
                        <h4>Marie Lemoine</h4>
                        <p>Responsable des réservations</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="team-card">
                        <img src="assets/images/team-member1.jpg" alt="Membre de l'équipe">
                        <h4>Ali Kamara</h4>
                        <p>Conseiller voyage</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Valeurs de l'entreprise -->
    <div class="values">
        <div class="container">
            <h2>Nos Valeurs</h2>

            <div class="row">
                <div class="col-md-4 value-item">
                    <h3>Fiabilité</h3>
                    <p>Nous offrons des services fiables et de qualité pour garantir que chaque voyage se déroule sans accroc.</p>
                </div>
                <div class="col-md-4 value-item">
                    <h3>Innovation</h3>
                    <p>Nous cherchons toujours à innover pour offrir des expériences uniques et mémorables à nos clients.</p>
                </div>
                <div class="col-md-4 value-item">
                    <h3>Service Client</h3>
                    <p>Notre priorité est de fournir un service client exceptionnel, disponible 24/7 pour répondre à toutes les demandes.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Histoire de Saf Voyage -->
    <div class="about-section">
        <div class="container">
            <h2>Notre Histoire</h2>
            <p>Saf Voyage a été fondée en 2010 avec la mission de rendre les voyages accessibles à tous. Depuis, nous avons parcouru du chemin en développant un réseau mondial d'hôtels, de vols, et d'excursions.</p>
            <p>Avec une équipe passionnée et dédiée, nous avons créé une entreprise qui s'efforce de redéfinir les voyages en offrant à nos clients des expériences uniques et personnalisées.</p>
        </div>
    </div>

    <!-- Section Contactez-nous -->
    <div class="about-section">
        <div class="container">
            <h2>Contactez-nous</h2>
            <p>Nous serions ravis de vous aider dans la planification de votre prochain voyage. N'hésitez pas à nous contacter pour toute question ou demande de réservation.</p>
            <ul>
                <li><strong>Email :</strong> support@safvoyage.com</li>
                <li><strong>Téléphone :</strong> +225 01 23 45 67 89</li>
                <li><strong>Adresse :</strong> Abidjan, Côte d'Ivoire</li>
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

<?php include('includes/footer.php'); ?>

</html>
