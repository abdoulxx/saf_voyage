<?php
session_start();
include('../config/config.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Statistiques pour le tableau de bord

// Nombre total d'utilisateurs
try {
    $query = "SELECT COUNT(*) as total_users FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
} catch (PDOException $e) {
    $total_users = 0;
}

// Nombre total de vols
try {
    $query = "SELECT COUNT(*) as total_vols FROM vols";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_vols = $stmt->fetch(PDO::FETCH_ASSOC)['total_vols'];
} catch (PDOException $e) {
    $total_vols = 0;
}

// Nombre total de réservations
try {
    $query = "SELECT COUNT(*) as total_reservations FROM reservations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['total_reservations'];
} catch (PDOException $e) {
    $total_reservations = 0;
}

// Nombre total d'hôtels
try {
    $query = "SELECT COUNT(*) as total_hotels FROM hotels";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_hotels = $stmt->fetch(PDO::FETCH_ASSOC)['total_hotels'];
} catch (PDOException $e) {
    $total_hotels = 0;
}

// Montant total des paiements
try {
    $query = "SELECT SUM(prix_total) as total_payments FROM reservations WHERE statut_paiement = 'paye'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $total_payments = $stmt->fetch(PDO::FETCH_ASSOC)['total_payments'];
    if ($total_payments === null) $total_payments = 0;
} catch (PDOException $e) {
    $total_payments = 0;
}

// Statistiques des statuts de paiement pour le graphique
try {
    $query = "SELECT statut_paiement, COUNT(*) as count FROM reservations GROUP BY statut_paiement";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $payment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le graphique
    $payment_labels = [];
    $payment_data = [];
    $payment_colors = [];
    
    $color_map = [
        'paye' => '#28a745',
        'en_attente' => '#ffc107',
        'non_paye' => '#dc3545',
        'annule' => '#6c757d'
    ];
    
    $label_map = [
        'paye' => 'Payé',
        'en_attente' => 'En attente',
        'non_paye' => 'Non payé',
        'annule' => 'Annulé'
    ];
    
    foreach ($payment_stats as $stat) {
        $status = $stat['statut_paiement'];
        $payment_labels[] = isset($label_map[$status]) ? $label_map[$status] : $status;
        $payment_data[] = $stat['count'];
        $payment_colors[] = isset($color_map[$status]) ? $color_map[$status] : '#007bff';
    }
    
    $payment_labels_json = json_encode($payment_labels);
    $payment_data_json = json_encode($payment_data);
    $payment_colors_json = json_encode($payment_colors);
} catch (PDOException $e) {
    $payment_labels_json = '[]';
    $payment_data_json = '[]';
    $payment_colors_json = '[]';
}

// Statistiques des réservations par destination pour le graphique
try {
    $query = "SELECT v.destination, COUNT(*) as count 
              FROM reservations r 
              JOIN vols v ON r.vol_id = v.id 
              GROUP BY v.destination 
              ORDER BY count DESC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $destination_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le graphique
    $destination_labels = [];
    $destination_data = [];
    
    foreach ($destination_stats as $stat) {
        $destination_labels[] = ucfirst($stat['destination']);
        $destination_data[] = $stat['count'];
    }
    
    $destination_labels_json = json_encode($destination_labels);
    $destination_data_json = json_encode($destination_data);
} catch (PDOException $e) {
    $destination_labels_json = '[]';
    $destination_data_json = '[]';
}

// Réservations récentes
try {
    $query = "SELECT r.id, r.prix_total, r.statut_paiement, r.date_reservation, 
              v.destination, u.nom as user_nom 
              FROM reservations r 
              JOIN vols v ON r.vol_id = v.id 
              JOIN users u ON r.user_id = u.id 
              ORDER BY r.date_reservation DESC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_reservations = [];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fa;
            font-family: 'Poppins', sans-serif;
            margin: 0;
        }

        #top-navbar {
            width: 100%;
            background-color: #2c3e50;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }

        #sidebar {
            width: 250px;
            height: 100vh;
            background-color: #2c3e50;
            position: fixed;
            top: 60px;
            left: 0;
            padding-top: 20px;
            overflow-y: auto;
        }

        #content {
            margin-left: 250px;
            padding: 80px 20px 20px 20px;
            width: 100%;
        }

        .stat-card {
            background-color: white;
            color: #343a40;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            margin-bottom: 20px;
        }

        .stat-card:hover {
            transform: scale(1.05);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #007bff;
            margin-bottom: 10px;
        }

        .stat-card h2 {
            font-size: 2rem;
            color: #007bff;
        }

        .stat-card p {
            font-size: 0.9rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
        }

        .col-md-4 {
            flex: 0 0 calc(16.66% - 20px);
            max-width: calc(16.66% - 20px);
            margin: 10px;
        }

        .chart-size {
            width: 100%;
            height: 250px;
        }

        .graphic-card {
            background-color: white;
            color: #343a40;
            border-radius: 15px;
            padding: 10px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
            max-width: 900px;
            margin: 0 auto;
        }

        .graphic-card h5 {
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        .graphic-card h6 {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    <?php include '../admin/top-navbar.php'; ?>
    <?php include '../admin/sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid">
            <h1 class="mt-4 mb-4">Tableau de Bord</h1>
            
            <!-- Cartes de statistiques -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Utilisateurs</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people-fill fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Réservations</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_reservations; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-calendar-check-fill fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Vols</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_vols; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-airplane-fill fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Revenu Total</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($total_payments, 0, ',', ' '); ?> FCFA</div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-currency-exchange fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row">
                <!-- Graphique des statuts de paiement -->
                <div class="col-xl-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Statuts des Paiements</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="paymentStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique des destinations populaires -->
                <div class="col-xl-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Destinations Populaires</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-bar pt-4 pb-2">
                                <canvas id="destinationsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Réservations récentes -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Réservations Récentes</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Client</th>
                                            <th>Destination</th>
                                            <th>Date</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_reservations as $reservation): ?>
                                            <tr>
                                                <td><?php echo $reservation['id']; ?></td>
                                                <td><?php echo htmlspecialchars($reservation['user_nom']); ?></td>
                                                <td><?php echo ucfirst(htmlspecialchars($reservation['destination'])); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></td>
                                                <td><?php echo number_format($reservation['prix_total'], 0, ',', ' '); ?> FCFA</td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    $status_text = '';
                                                    
                                                    switch($reservation['statut_paiement']) {
                                                        case 'paye':
                                                            $status_class = 'success';
                                                            $status_text = 'Payé';
                                                            break;
                                                        case 'en_attente':
                                                            $status_class = 'warning';
                                                            $status_text = 'En attente';
                                                            break;
                                                        case 'non_paye':
                                                            $status_class = 'danger';
                                                            $status_text = 'Non payé';
                                                            break;
                                                        case 'annule':
                                                            $status_class = 'secondary';
                                                            $status_text = 'Annulé';
                                                            break;
                                                        default:
                                                            $status_class = 'info';
                                                            $status_text = $reservation['statut_paiement'];
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($recent_reservations)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Aucune réservation récente</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Graphique des statuts de paiement
        var ctxPie = document.getElementById('paymentStatusChart').getContext('2d');
        var paymentStatusChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: <?php echo $payment_labels_json; ?>,
                datasets: [{
                    data: <?php echo $payment_data_json; ?>,
                    backgroundColor: <?php echo $payment_colors_json; ?>,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Graphique des destinations populaires
        var ctxBar = document.getElementById('destinationsChart').getContext('2d');
        var destinationsChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?php echo $destination_labels_json; ?>,
                datasets: [{
                    label: 'Nombre de réservations',
                    data: <?php echo $destination_data_json; ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
