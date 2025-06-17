<?php
session_start();
require_once 'config/config.php';

// Vérifier si l'ID du vol est fourni
if (!isset($_GET['vol_id']) || empty($_GET['vol_id'])) {
    header("Location: index.php");
    exit;
}

$vol_id = $_GET['vol_id'];

// Récupérer les informations du vol
try {
    $query = "SELECT * FROM vols WHERE id = :vol_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vol_id', $vol_id);
    $stmt->execute();
    $vol = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$vol) {
        header("Location: index.php");
        exit;
    }
    
    // Récupérer les hôtels associés à ce vol
    $query = "SELECT * FROM hotels WHERE vol_id = :vol_id ORDER BY prix_nuit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vol_id', $vol_id);
    $stmt->execute();
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les options disponibles
    $query = "SELECT * FROM options ORDER BY prix";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $options_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater les options pour faciliter l'accès
    $options = [];
    foreach ($options_data as $option) {
        $options[$option['nom']] = $option['prix'];
    }
    
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des données: " . $e->getMessage();
}

// Variables pour la page
$destination = $vol['destination'];
$prix_base = $vol['prix'];
$nombre_jours = $vol['duree'];

// Initialisation du prix total
$prix_total = $prix_base;

// Si l'utilisateur soumet le formulaire avec une sélection d'hôtel et d'options
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['hotel']) && !empty($_POST['hotel'])) {
        // Récupérer le prix de l'hôtel sélectionné
        $hotel_id = $_POST['hotel'];
        $query = "SELECT prix_nuit FROM hotels WHERE id = :hotel_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':hotel_id', $hotel_id);
        $stmt->execute();
        $hotel_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hotel_data) {
            $prix_total += $hotel_data['prix_nuit'] * $nombre_jours; // Ajouter le prix de l'hôtel sélectionné au total
        }
    }
    
    $options_selected = isset($_POST['options']) ? $_POST['options'] : [];
    
    // Ajouter le prix des options sélectionnées
    foreach ($options_selected as $option) {
        $prix_total += $options[$option] * $nombre_jours;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Description" content="Détails de votre réservation - Saf Voyage">
    <title>Détails de Réservation - Saf Voyage</title>
    <!-- Intégration de Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>

    <!-- En-tête -->
    <?php include('includes/head.php'); ?>
    <?php include('includes/navbar.php'); ?>

    <!-- Section Détails de la réservation -->
    <section class="reservation-details">
        <div class="container">
            <h2>Réservation : <?php echo ucfirst($vol['destination']); ?></h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Card contenant les options de réservation -->
            <div class="reservation-card">
                <div class="details-left">
                    <?php if (!empty($vol['image']) && file_exists("assets/images/" . $vol['image'])): ?>
                        <img src="assets/images/<?php echo $vol['image']; ?>" alt="<?php echo ucfirst($destination); ?>" class="details-image">
                    <?php else: ?>
                        <img src="assets/images/default.jpg" alt="<?php echo ucfirst($destination); ?>" class="details-image">
                    <?php endif; ?>
                </div>

                <div class="details-right">
                    <h3>Description</h3>
                    <p>Choisissez parmi les options suivantes pour personnaliser votre voyage à <?php echo ucfirst($vol['destination']); ?>.</p>
                    <p><strong>Dates:</strong> Du <?php echo date('d/m/Y', strtotime($vol['date_depart'])); ?> au <?php echo date('d/m/Y', strtotime($vol['date_retour'])); ?> (<?php echo $vol['duree']; ?> jours)</p>
                    <p><strong>Prix de base:</strong> <?php echo number_format($vol['prix'], 0, ',', ' '); ?> FCFA</p>

                    <form method="POST" action="traitement-reservation.php">

                        <!-- Choix d'un hôtel -->
                        <h4>Souhaitez-vous un hôtel ?</h4>
                        <label><input type="radio" name="hotel_choice" value="oui" onclick="showHotelPopup()"> Oui</label>
                        <label><input type="radio" name="hotel_choice" value="non" onclick="showPaymentOptions()"> Non</label>

                        <!-- Affichage du pop-up avec les hôtels -->
                        <div id="hotel-selection-popup" class="popup" style="display:none;">
                            <div class="popup-content">
                                <h3>Choisissez votre hôtel</h3>
                                <div class="hotel-cards row">
                                    <?php if (empty($hotels)): ?>
                                        <div class="alert alert-info">Aucun hôtel n'est disponible pour cette destination.</div>
                                    <?php else: ?>
                                        <?php foreach ($hotels as $hotel) : ?>
                                            <div class="col-md-4 hotel-card" onclick="selectHotel('<?php echo $hotel['id']; ?>', '<?php echo $hotel['nom']; ?>', '<?php echo $hotel['prix_nuit']; ?>', '<?php echo $hotel['image']; ?>', '<?php echo $hotel['localisation']; ?>')">
                                                <div class="card" style="cursor: pointer;">
                                                    <?php if (!empty($hotel['image']) && file_exists("assets/images/" . $hotel['image'])): ?>
                                                        <img src="assets/images/<?php echo $hotel['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($hotel['nom']); ?>">
                                                    <?php else: ?>
                                                        <img src="assets/images/default.jpg" class="card-img-top" alt="Image par défaut">
                                                    <?php endif; ?>
                                                    <div class="card-body">
                                                        <h5 class="card-title"><?php echo htmlspecialchars($hotel['nom']); ?></h5>
                                                        <p class="card-text">Localisation: <?php echo htmlspecialchars($hotel['localisation']); ?></p>
                                                        <p class="card-text">Prix par nuit: <?php echo number_format($hotel['prix_nuit'], 0, ',', ' '); ?> FCFA</p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-secondary" onclick="closePopup()">Fermer</button>
                            </div>
                        </div>

                        <!-- Sélection d'hôtel (mis à jour après le choix dans le pop-up) -->
                        <div id="hotel-selection" style="display:none;">
                            <h4>Votre hôtel sélectionné :</h4>
                            <p><strong id="hotel-name">Aucun hôtel sélectionné</strong></p>
                            <p><strong id="hotel-price">Prix: 0 FCFA</strong></p>
                            <p><strong id="hotel-localisation">Localisation: Non spécifiée</strong></p>
                            <input type="hidden" name="hotel" id="hotel" value="">
                            <input type="hidden" name="vol_id" value="<?php echo $vol_id; ?>">
                        </div>

                        <!-- Options supplémentaires -->
                        <div id="options-section" style="display:none;">
                            <h4>Options supplémentaires</h4>
                            <?php if (!empty($options)): ?>
                                <?php foreach ($options as $option => $prix) : ?>
                                    <div class="option">
                                        <input type="checkbox" name="options[]" value="<?php echo $option; ?>" id="<?php echo str_replace(' ', '_', $option); ?>" data-price="<?php echo $prix; ?>" onchange="updatePrice()">
                                        <label for="<?php echo str_replace(' ', '_', $option); ?>"><?php echo $option; ?> - <?php echo number_format($prix, 0, ',', ' '); ?> FCFA</label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Aucune option supplémentaire n'est disponible pour le moment.</p>
                            <?php endif; ?>

                            <h4>Prix Total :</h4>
                            <p><strong id="total_price"><?php echo $prix_total; ?> FCFA</strong></p>
                            <input type="hidden" name="prix_total" id="prix_total_input" value="<?php echo $prix_total; ?>">

                            <h4>Modalités de paiement</h4>
                            <label><input type="radio" name="payment_method" value="online"> Paiement en ligne</label><br>
                            <label><input type="radio" name="payment_method" value="reception"> Paiement à la réception du billet d'avion</label><br>

                            <button type="submit" class="btn-reservation">Confirmer la réservation</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include('includes/footer.php'); ?>

    <!-- Script pour gestion du pop-up et mise à jour dynamique du prix -->
    <script>
        // Afficher le pop-up de sélection de l'hôtel
        function showHotelPopup() {
            document.getElementById('hotel-selection-popup').style.display = 'block';
            document.getElementById('options-section').style.display = 'none';
        }

        // Sélectionner l'hôtel et fermer le pop-up
        function selectHotel(id, nom, prix, image, localisation) {
            // Mettre à jour le choix de l'hôtel dans le formulaire
            document.getElementById('hotel-selection').style.display = 'block';
            document.getElementById('hotel-selection-popup').style.display = 'none';
            document.getElementById('hotel-name').innerText = nom;
            document.getElementById('hotel-price').innerText = prix + ' FCFA';
            document.getElementById('hotel-localisation').innerText = localisation;

            // Stocker la valeur de l'hôtel sélectionné dans un champ caché
            document.getElementById('hotel').value = id;
            // Stocker également le prix de l'hôtel dans un attribut data
            document.getElementById('hotel').setAttribute('data-price', prix);

            // Afficher les options supplémentaires
            document.getElementById('options-section').style.display = 'block';
            updatePrice();
        }

        // Fermer le pop-up sans sélection
        function closePopup() {
            document.getElementById('hotel-selection-popup').style.display = 'none';
        }

        // Afficher les options de paiement si l'utilisateur ne veut pas d'hôtel
        function showPaymentOptions() {
            document.getElementById('options-section').style.display = 'block';
            document.getElementById('hotel-selection').style.display = 'none';
        }

        // Mettre à jour le prix total en fonction des choix
        function updatePrice() {
            let totalPrice = <?php echo $prix_base; ?>; // Prix de base

            // Ajouter le prix de l'hôtel sélectionné
            const hotelInput = document.getElementById('hotel');
            if (hotelInput && hotelInput.value !== '') {
                // Récupérer le prix de l'hôtel à partir de l'attribut data-price
                const hotelPrice = parseInt(hotelInput.getAttribute('data-price'));
                // Multiplier par le nombre de jours
                totalPrice += hotelPrice * <?php echo $nombre_jours; ?>;
            }

            // Ajouter le prix des options sélectionnées
            document.querySelectorAll('input[type="checkbox"]:checked').forEach(option => {
                totalPrice += parseInt(option.dataset.price);
            });

            // Afficher le prix total
            document.getElementById('total_price').innerText = totalPrice + " FCFA";
            
            // Mettre à jour le champ caché pour le formulaire
            document.getElementById('prix_total_input').value = totalPrice;
        }
    </script>

    <!-- Intégration de Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>








    <style>
.reservation-details {
    padding: 50px 20px;
    background-color: #f8f8f8;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

h2 {
    font-size: 2.5rem;
    font-weight: bold;
    color: #333;
    margin-bottom: 30px;
    text-align: center;
}

.reservation-card {
    display: flex;
    gap: 30px;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 30px;
    margin-top: 30px;
}

.details-left {
    width: 40%;
}

.details-right {
    width: 60%;
}

.details-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.details-right h3, .details-right h4 {
    font-size: 1.8rem;
    color: #0066cc;
    margin-top: 20px;
}

.details-right p {
    font-size: 1.1rem;
    color: #555;
}

form {
    display: flex;
    flex-direction: column;
    margin-top: 20px;
}

form input[type="radio"], form input[type="checkbox"] {
    margin-right: 10px;
}

form label {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 10px;
}

form button {
    background-color: rgba(6, 19, 116, 0.47);
    color: white;
    padding: 12px 24px;
    font-size: 1.2rem;
    font-weight: bold;
    border-radius: 8px;
    transition: background-color 0.3s ease;
    margin-top: 20px;
}

form button:hover {
    background-color: rgb(10, 31, 122);
}

.hotel-cards {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-bottom: 20px;
}

.hotel-card {
    background-color: #f4f4f4;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform 0.3s ease;
}

.hotel-card:hover {
    transform: scale(1.05);
}

/* CSS pour le pop-up et la mise en forme générale */
.popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.popup-content {
    background-color: white;
    padding: 40px;
    border-radius: 8px;
    max-width: 800px;
    width: 80%;
}

.hotel-cards {
    display: flex;
    gap: 20px;
    justify-content: center;
    margin-bottom: 20px;
}

.hotel-card {
    background-color: #f4f4f4;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform 0.3s ease;
}

.hotel-card:hover {
    transform: scale(1.05);
}

#hotel-selection-popup {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 999;
}

form {
    display: flex;
    flex-direction: column;
    margin-top: 20px;
}

form input[type="radio"], form input[type="checkbox"] {
    margin-right: 10px;
}

form label {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 10px;
}

form button {
    background-color: rgba(6, 19, 116, 0.47);
    color: white;
    padding: 12px 24px;
    font-size: 1.2rem;
    font-weight: bold;
    border-radius: 8px;
    transition: background-color 0.3s ease;
    margin-top: 20px;
}

form button:hover {
    background-color: rgb(10, 31, 122);
}

#options-section {
    display: none;
}

option {
    margin-bottom: 10px;
}

    </style>
