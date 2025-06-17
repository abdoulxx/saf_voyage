<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include('includes/head.php'); ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services de Saf Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: 'Poppins', sans-serif;
        }

        .service-card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-top: 20px;
            background-color: white;
            padding: 20px;
            text-align: center;
        }

        .service-icon {
            font-size: 3rem;
            color: #007bff;
        }

        .section-title {
            margin-top: 50px;
            color: #007bff;
            text-align: center;
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .service-description {
            color: #555;
            font-size: 1.1rem;
        }

        .service-card:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
        }
    </style>
</head>

<body>
    <?php include('includes/navbar.php'); ?>

    <div class="container mt-5">
        <h2 class="section-title">Nos Services - Saf Voyage</h2>

        <div class="row">
            <!-- Service: Réservation de Vols -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="bi bi-airplane-engines"></i>
                    </div>
                    <h3>Réservation de Vols</h3>
                    <p class="service-description">Réservez facilement vos vols vers votre destination de rêve avec Saf Voyage. Profitez des meilleures offres.</p>
                </div>
            </div>
            <!-- Service: Hébergement -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="bi bi-house-door"></i>
                    </div>
                    <h3>Hébergements</h3>
                    <p class="service-description">Nous vous aidons à choisir les meilleurs hôtels et hébergements, en fonction de vos besoins et de votre budget.</p>
                </div>
            </div>
            <!-- Service: Excursions -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="bi bi-camera"></i>
                    </div>
                    <h3>Excursions et Activités</h3>
                    <p class="service-description">Explorez les attractions locales avec nos excursions guidées et activités recommandées pour une expérience inoubliable.</p>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <!-- Service: Assistance 24/7 -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <h3>Assistance 24/7</h3>
                    <p class="service-description">Notre équipe est disponible 24/7 pour vous assister dans vos réservations et répondre à toutes vos questions pendant votre voyage.</p>
                </div>
            </div>
            <!-- Service: Transfert Aéroport -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="bi bi-car-front"></i>
                    </div>
                    <h3>Service de Transfert Aéroport</h3>
                    <p class="service-description">Nous proposons un service de transport entre l'aéroport et votre hôtel pour rendre votre voyage encore plus confortable.</p>
                </div>
            </div>
            <!-- Service: Assurance Voyage -->
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h3>Assurance Voyage</h3>
                    <p class="service-description">Protégez-vous et votre voyage avec nos offres d'assurance, pour des vacances en toute sérénité.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<?php include('includes/footer.php'); ?>

</html>
