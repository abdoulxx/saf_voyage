<?php
session_start();
require_once 'config/config.php';

// Récupérer tous les vols
try {
    $query = "SELECT * FROM vols ORDER BY date_depart ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $vols = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des vols: " . $e->getMessage();
    $vols = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réserver un voyage - Saf Voyage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: rgba(6, 19, 116, 0.47);
            --primary-color-hover: rgb(10, 31, 122);
        }

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
            width: 100%;
            text-align: center;
            flex-basis: 100%;
        }

        .reservation-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 30%;
            margin: 0;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .reservation-card:hover {
            transform: scale(1.05);
        }

        .card-image img {
            width: 100%;
            height: 200px;
            object-fit: cover;
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
            background-color: var(--primary-color);
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
            background-color: var(--primary-color-hover);
        }

        @media (max-width: 768px) {
            .reservation-card {
                width: 90%;
                margin: 10px auto;
            }
        }
    </style>
</head>
<body>
    <?php include('includes/head.php'); ?>
    <?php include('includes/navbar.php'); ?>

    <!-- Liste des vols -->
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
        <?php endif; ?>
    </section>

    <?php include('includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 