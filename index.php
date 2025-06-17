<?php
session_start();

require 'config/config.php';

// Récupérer les vols depuis la base de données
try {
    $query = "SELECT * FROM vols ORDER BY created_at DESC LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $vols = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des vols: " . $e->getMessage();
}

?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <?php include('includes/head.php'); ?>

</head>

<body>
    <?php include('includes/navbar.php'); ?>

    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">
                Découvrez votre prochaine aventure avec
                <span class="highlight" id="musiciens">Saf Voyage</span>
                Réservez des expériences
                <span class="highlight" id="instruments">inoubliable,</span>
                à portée de clic !
            </h1>
            <a href="reserver.php" class="btn-reserver">Réserver maintenant</a>

        </div>
        <div class="hero-image">
            <img src="assets/images/voyage1.png" alt="">
        </div>
    </section>
    <style>
        .hero {
            display: flex;
            align-items: center;
            /* Centre le contenu verticalement */
            justify-content: center;
            /* Centre le contenu horizontalement */
            background-color: rgb(255, 255, 255);
            /* Fond blanc */
            padding: 50px;
            height: 80vh;
            /* Hauteur de la section */
            position: relative;
            text-align: center;
            /* Centre le texte */
        }

        .hero-content {
            max-width: 600px;
            /* Limite la largeur du texte pour une meilleure lisibilité */
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
        }


        .btn-reserver {
            display: inline-block;
            background-color: rgba(6, 19, 116, 0.47);
            color: white;
            padding: 12px 24px;
            font-size: 1.2rem;
            font-weight: bold;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .btn-reserver:hover {
            background-color: rgb(10, 31, 122);
        }

        .hero-image img {
            max-width: 100%;
            /* Assure que l'image occupe toute la largeur possible */
            height: auto;
            /* Maintient le ratio de l'image */
            object-fit: cover;
            /* S'assure que l'image est bien ajustée sans déformation */
        }
    </style>

    <h2 class="section-title"><span id="highlight-musiciens">Nos Services</span></h2>

    <section class="features">

        <!-- Service 1 : Réservation de vols -->
        <div class="feature-item">
            <div class="feature-icon">
                <img src="assets/images/vol.png" alt="Réservation de Vols" class="feature-image">
            </div>
            <h3 class="feature-title">Réservation de Vols</h3>
            <p class="feature-description">Réservez vos vols à des prix compétitifs, tout en choisissant la meilleure option pour votre voyage.</p>
        </div>

        <!-- Service 2 : Séjours et Hôtels -->
        <div class="feature-item">
            <div class="feature-icon">
                <img src="assets/images/hotel.png" alt="Séjours et Hôtels" class="feature-image">
            </div>
            <h3 class="feature-title">Séjours et Hôtels</h3>
            <p class="feature-description">Choisissez parmi une sélection d'hôtels de qualité pour des séjours inoubliables dans les destinations de votre choix.</p>
        </div>

        <!-- Service 3 : Excursions et Activités -->
        <div class="feature-item">
            <div class="feature-icon">
                <img src="assets/images/activite.png" alt="Excursions et Activités" class="feature-image">
            </div>
            <h3 class="feature-title">Excursions et Activités</h3>
            <p class="feature-description">Participez à des excursions guidées et des activités sur place pour découvrir les meilleures attractions locales.</p>
        </div>

    </section>

    <section class="reservation-cards">
    <h2>Nos Offres de Réservation</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (empty($vols)): ?>
        <div class="alert alert-info">Aucune offre de voyage n'est disponible pour le moment.</div>
    <?php else: ?>
        <?php foreach ($vols as $vol): ?>
            <div class="reservation-card">
                <div class="card-image">
                    <?php if (!empty($vol['image']) && file_exists("assets/images/" . $vol['image'])): ?>
                        <img src="assets/images/<?php echo $vol['image']; ?>" alt="<?php echo htmlspecialchars($vol['destination']); ?>">
                    <?php else: ?>
                        <img src="assets/images/default.jpg" alt="Image par défaut">
                    <?php endif; ?>
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo htmlspecialchars($vol['ville_depart']); ?> - <?php echo htmlspecialchars($vol['destination']); ?> - <?php echo $vol['duree']; ?> jours</h3>
                    <p class="card-description"><?php echo nl2br(htmlspecialchars($vol['description'])); ?></p>
                    <p class="card-dates">Départ: <?php echo date('d/m/Y', strtotime($vol['date_depart'])); ?> | Retour: <?php echo date('d/m/Y', strtotime($vol['date_retour'])); ?></p>
                    <p class="card-price"><strong>Prix :</strong> <?php echo number_format($vol['prix'], 0, ',', ' '); ?> FCFA</p>
                    <a href="reservation-details.php?vol_id=<?php echo $vol['id']; ?>" class="btn-reservation">Réservez maintenant</a>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="w-100 text-center mt-4"> <a href="reserver.php" class="btn-reservation">Voir tous nos vols</a> </div>
    <?php endif; ?>
</section>

<style>
.reservation-cards {
    padding: 50px 20px;
    background-color: #f4f4f4;
    text-align: center;
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
}

.reservation-cards h2 {
    font-size: 2.5rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 40px;
    width: 100%;  /* Centrer le titre */
    text-align: center;
    flex-basis: 100%; /* Pour que le titre prenne toute la largeur */
}

.reservation-card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    width: 30%;  /* Chaque carte prendra 30% de l'espace disponible */
    margin: 0;  /* Suppression de la marge verticale */
    text-align: center;
    transition: transform 0.3s ease;
}

.reservation-card:hover {
    transform: scale(1.05);  /* Effet de soulèvement au survol */
}

.card-image img {
    width: 100%;
    height: 200px;
    object-fit: cover;  /* Assure que l'image est bien ajustée sans déformation */
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.card-content {
    padding: 20px;
}

.card-title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #0066cc;
    margin-bottom: 15px;
}

.card-description {
    font-size: 1.1rem;
    color: #555;
    margin-bottom: 10px;
}

.card-dates, .card-price {
    font-size: 1rem;
    color: #333;
    margin-bottom: 10px;
}

.btn-reservation {
    display: inline-block;
    background-color: rgba(6, 19, 116, 0.47);
    color: white;
    padding: 12px 24px;
    font-size: 1.2rem;
    font-weight: bold;
    text-decoration: none;
    border-radius: 8px;
    margin-top: 20px;
    transition: background-color 0.3s ease;
}

.btn-reservation:hover {
    background-color: rgb(10, 31, 122);
}

@media (max-width: 768px) {
    .reservation-card {
        width: 90%;  /* Prend plus de place sur les petits écrans */
        margin: 10px auto;  /* Centre les cartes et réduit l'espacement */
    }
}


</style>

    <section class="booking-process">
        <h2>Comment ça marche ?</h2>
        <div class="process-steps">
            <div class="process-step">
                <div class="process-icon">
                    <i class="fas fa-map-marker-alt"></i> <!-- Icône de localisation -->
                </div>
                <h3>1. Choisissez votre destination</h3>
                <p>Sélectionnez la destination de votre choix parmi nos offres.</p>
            </div>
            <div class="process-step">
                <div class="process-icon">
                    <i class="fas fa-plane"></i> <!-- Icône d'avion -->
                </div>
                <h3>2. Réservez votre vol et hébergement</h3>
                <p>Réservez facilement votre vol et votre hôtel en quelques clics.</p>
            </div>
            <div class="process-step">
                <div class="process-icon">
                    <i class="fas fa-sun"></i> <!-- Icône de soleil -->
                </div>
                <h3>3. Profitez de vos vacances</h3>
                <p>Profitez de vos vacances sans souci, avec notre assistance 24/7.</p>
            </div>
        </div>
    </section>

    <style>
        .booking-process {
            background-color: #f8f8f8;
            /* Fond léger pour la section */
            padding: 50px 20px;
            /* Espacement autour de la section */
            text-align: center;
            /* Centrer le texte */
            margin: 50px 0;
            /* Espacement autour de la section */
        }

        .booking-process h2 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            /* Couleur du titre */
            margin-bottom: 30px;
        }

        .process-steps {
            display: flex;
            justify-content: space-around;
            /* Espacement égal entre les éléments */
            gap: 30px;
            /* Espacement entre chaque étape */
            flex-wrap: wrap;
            /* Permet aux éléments de s'ajuster sur des écrans plus petits */
        }

        .process-step {
            background-color: white;
            /* Fond blanc pour chaque étape */
            border-radius: 8px;
            /* Coins arrondis */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Ombre légère */
            padding: 30px;
            width: 30%;
            /* Largeur fixe de chaque étape */
            transition: transform 0.3s ease;
            /* Animation pour un effet visuel */
            text-align: center;
            /* Centrer le texte dans chaque étape */
        }

        .process-step:hover {
            transform: translateY(-10px);
            /* Effet de soulèvement au survol */
        }

        .process-icon {
            font-size: 3rem;
            color: #0066cc;
            /* Couleur de l'icône */
            margin-bottom: 15px;
            /* Espacement entre l'icône et le texte */
        }

        .process-step h3 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            /* Couleur pour le titre de chaque étape */
            margin-bottom: 10px;
        }

        .process-step p {
            font-size: 1.1rem;
            color: #555;
            /* Couleur plus claire pour le texte descriptif */
        }

        @media (max-width: 768px) {
            .process-step {
                width: 100%;
                /* Prend toute la largeur sur les petits écrans */
            }
        }
    </style>



    <section class="temoignages">
        <h2 class="section-title text-center"><span id="highlight-temoignages">Témoignages</span></h2>

        <div class="temoignage-card-container">
            <!-- Témoignage 1 -->
            <div class="temoignage-card">
                <p class="temoignage-text">"Séjour incroyable à l'hôtel Luxe ! Le personnel était très accueillant, et la chambre était parfaitement propre et confortable. Je reviendrai sans hésiter."</p>
                <p class="temoignage-author">- Jean Dupont</p>
            </div>

            <!-- Témoignage 2 -->
            <div class="temoignage-card">
                <p class="temoignage-text">"Excellente expérience. La vue depuis la suite était magnifique, et le service était impeccable. Merci à toute l'équipe !" </p>
                <p class="temoignage-author">- Claire Martin</p>
            </div>

            <!-- Témoignage 3 -->
            <div class="temoignage-card">
                <p class="temoignage-text">"Un endroit parfait pour se détendre. Le spa et la piscine étaient un vrai plus. Nous avons passé un séjour merveilleux. À recommander !" </p>
                <p class="temoignage-author">- Michel Lefevre</p>
            </div>
        </div>
    </section>

    <style>
        .temoignages {
            padding: 50px 0;
            background-color: #f9f9f9;
        }

        .temoignage-card-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .temoignage-card {
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
        }

        .temoignage-text {
            font-size: 1rem;
            color: #555;
            font-style: italic;
            margin-bottom: 15px;
        }

        .temoignage-author {
            font-size: 1.1rem;
            font-weight: bold;
            color: #FF9800;
        }
    </style>



    <section class="localisation">
        <h2 class="section-title"><span id="highlight-localisation">Localisation</span></h2>
        <div class="map-container">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3972.7715300208065!2d-3.988692134641068!3d5.298307141698354!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xfc1ec003d6e0643%3A0x972df26db70e367c!2sCap%20Sud%20Mall!5e0!3m2!1sen!2sci!4v1745873671782!5m2!1sen!2sci" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>    </section>

    <?php include('includes/footer.php'); ?>

</body>

</html>