<?php
session_start();
include('../config/config.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Période de filtrage
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'mois';
$date_debut = '';
$date_fin = '';

// Définir les dates de début et de fin en fonction de la période
switch ($periode) {
    case 'semaine':
        $date_debut = date('Y-m-d', strtotime('-7 days'));
        $date_fin = date('Y-m-d');
        break;
    case 'mois':
        $date_debut = date('Y-m-d', strtotime('-30 days'));
        $date_fin = date('Y-m-d');
        break;
    case 'trimestre':
        $date_debut = date('Y-m-d', strtotime('-90 days'));
        $date_fin = date('Y-m-d');
        break;
    case 'annee':
        $date_debut = date('Y-m-d', strtotime('-365 days'));
        $date_fin = date('Y-m-d');
        break;
    case 'personnalise':
        $date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : date('Y-m-d', strtotime('-30 days'));
        $date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : date('Y-m-d');
        break;
}

// Statistiques générales des paiements
try {
    // Montant total des paiements
    $query = "SELECT SUM(prix_total) as total_payments FROM reservations WHERE statut_paiement = 'paye'";
    if ($periode !== 'tout') {
        $query .= " AND date_paiement BETWEEN :date_debut AND :date_fin";
    }
    $stmt = $db->prepare($query);
    if ($periode !== 'tout') {
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
    }
    $stmt->execute();
    $total_payments = $stmt->fetch(PDO::FETCH_ASSOC)['total_payments'];
    if ($total_payments === null) $total_payments = 0;
    
    // Nombre de paiements par statut
    $query = "SELECT statut_paiement, COUNT(*) as count FROM reservations";
    if ($periode !== 'tout') {
        $query .= " WHERE date_reservation BETWEEN :date_debut AND :date_fin";
    }
    $query .= " GROUP BY statut_paiement";
    $stmt = $db->prepare($query);
    if ($periode !== 'tout') {
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
    }
    $stmt->execute();
    $payment_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le graphique des statuts
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
    
    // Évolution des paiements par mois
    $query = "SELECT 
                DATE_FORMAT(date_paiement, '%Y-%m') as month,
                SUM(prix_total) as total
              FROM 
                reservations
              WHERE 
                statut_paiement = 'paye' AND date_paiement IS NOT NULL";
    if ($periode !== 'tout') {
        $query .= " AND date_paiement BETWEEN :date_debut AND :date_fin";
    }
    $query .= " GROUP BY month ORDER BY month";
    $stmt = $db->prepare($query);
    if ($periode !== 'tout') {
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
    }
    $stmt->execute();
    $monthly_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le graphique d'évolution
    $months_labels = [];
    $months_data = [];
    
    foreach ($monthly_payments as $payment) {
        $date = new DateTime($payment['month'] . '-01');
        $months_labels[] = $date->format('M Y');
        $months_data[] = $payment['total'];
    }
    
    $months_labels_json = json_encode($months_labels);
    $months_data_json = json_encode($months_data);
    
    // Paiements par méthode
    $query = "SELECT 
                methode_paiement,
                COUNT(*) as count
              FROM 
                reservations
              WHERE 
                statut_paiement = 'paye'";
    if ($periode !== 'tout') {
        $query .= " AND date_paiement BETWEEN :date_debut AND :date_fin";
    }
    $query .= " GROUP BY methode_paiement";
    $stmt = $db->prepare($query);
    if ($periode !== 'tout') {
        $stmt->bindParam(':date_debut', $date_debut);
        $stmt->bindParam(':date_fin', $date_fin);
    }
    $stmt->execute();
    $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le graphique des méthodes
    $methods_labels = [];
    $methods_data = [];
    $methods_colors = ['#4e73df', '#1cc88a'];
    
    foreach ($payment_methods as $method) {
        $method_name = $method['methode_paiement'] === 'reception' ? 'À la réception' : 'En ligne';
        $methods_labels[] = $method_name;
        $methods_data[] = $method['count'];
    }
    
    $methods_labels_json = json_encode($methods_labels);
    $methods_data_json = json_encode($methods_data);
    $methods_colors_json = json_encode($methods_colors);
    
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des statistiques: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques des Paiements - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stats-card {
            border-left: 4px solid;
            border-radius: 4px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .stats-card.primary {
            border-left-color: #4e73df;
        }
        
        .stats-card.success {
            border-left-color: #1cc88a;
        }
        
        .stats-card.info {
            border-left-color: #36b9cc;
        }
        
        .stats-card.warning {
            border-left-color: #f6c23e;
        }
        
        .stats-card-body {
            padding: 1.25rem;
        }
        
        .stats-card-title {
            text-transform: uppercase;
            margin-bottom: 0.25rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: #4e73df;
        }
        
        .stats-card-value {
            color: #5a5c69;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 0;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <?php include '../admin/top-navbar.php'; ?>
    <?php include '../admin/sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Statistiques des Paiements</h1>
                <a href="liste_paiements.php" class="btn btn-primary">
                    <i class="bi bi-list"></i> Liste des paiements
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Filtres de période -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Période</h6>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="periode" class="form-label">Sélectionner une période</label>
                            <select class="form-select" id="periode" name="periode" onchange="toggleDateInputs()">
                                <option value="semaine" <?php echo $periode === 'semaine' ? 'selected' : ''; ?>>Dernière semaine</option>
                                <option value="mois" <?php echo $periode === 'mois' ? 'selected' : ''; ?>>Dernier mois</option>
                                <option value="trimestre" <?php echo $periode === 'trimestre' ? 'selected' : ''; ?>>Dernier trimestre</option>
                                <option value="annee" <?php echo $periode === 'annee' ? 'selected' : ''; ?>>Dernière année</option>
                                <option value="personnalise" <?php echo $periode === 'personnalise' ? 'selected' : ''; ?>>Personnalisée</option>
                                <option value="tout" <?php echo $periode === 'tout' ? 'selected' : ''; ?>>Tout</option>
                            </select>
                        </div>
                        <div class="col-md-3 date-input" style="display: <?php echo $periode === 'personnalise' ? 'block' : 'none'; ?>">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $date_debut; ?>">
                        </div>
                        <div class="col-md-3 date-input" style="display: <?php echo $periode === 'personnalise' ? 'block' : 'none'; ?>">
                            <label for="date_fin" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $date_fin; ?>">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cartes de statistiques -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card primary">
                        <div class="stats-card-body">
                            <div class="stats-card-title">Montant total des paiements</div>
                            <div class="stats-card-value"><?php echo number_format($total_payments, 0, ',', ' '); ?> FCFA</div>
                        </div>
                    </div>
                </div>
                
                <?php 
                $total_reservations = array_sum($payment_data);
                $paye_count = 0;
                $en_attente_count = 0;
                $non_paye_count = 0;
                
                foreach ($payment_stats as $stat) {
                    if ($stat['statut_paiement'] === 'paye') {
                        $paye_count = $stat['count'];
                    } elseif ($stat['statut_paiement'] === 'en_attente') {
                        $en_attente_count = $stat['count'];
                    } elseif ($stat['statut_paiement'] === 'non_paye') {
                        $non_paye_count = $stat['count'];
                    }
                }
                ?>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card success">
                        <div class="stats-card-body">
                            <div class="stats-card-title">Réservations payées</div>
                            <div class="stats-card-value"><?php echo $paye_count; ?> (<?php echo $total_reservations > 0 ? round(($paye_count / $total_reservations) * 100) : 0; ?>%)</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card warning">
                        <div class="stats-card-body">
                            <div class="stats-card-title">Réservations en attente</div>
                            <div class="stats-card-value"><?php echo $en_attente_count; ?> (<?php echo $total_reservations > 0 ? round(($en_attente_count / $total_reservations) * 100) : 0; ?>%)</div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card stats-card info">
                        <div class="stats-card-body">
                            <div class="stats-card-title">Réservations non payées</div>
                            <div class="stats-card-value"><?php echo $non_paye_count; ?> (<?php echo $total_reservations > 0 ? round(($non_paye_count / $total_reservations) * 100) : 0; ?>%)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row">
                <!-- Graphique des statuts de paiement -->
                <div class="col-xl-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Répartition des statuts</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="paymentStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique des méthodes de paiement -->
                <div class="col-xl-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Méthodes de paiement</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="paymentMethodsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique d'évolution des paiements -->
                <div class="col-xl-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Évolution des paiements</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="paymentEvolutionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Derniers paiements -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Derniers paiements</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Destination</th>
                                    <th>Date de paiement</th>
                                    <th>Montant</th>
                                    <th>Méthode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $query = "SELECT r.id, r.prix_total, r.methode_paiement, r.date_paiement, 
                                              v.destination, u.nom as user_nom
                                              FROM reservations r
                                              JOIN vols v ON r.vol_id = v.id
                                              JOIN users u ON r.user_id = u.id
                                              WHERE r.statut_paiement = 'paye' AND r.date_paiement IS NOT NULL
                                              ORDER BY r.date_paiement DESC
                                              LIMIT 10";
                                    $stmt = $db->prepare($query);
                                    $stmt->execute();
                                    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                } catch (PDOException $e) {
                                    $recent_payments = [];
                                }
                                ?>
                                
                                <?php if (empty($recent_payments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Aucun paiement récent</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['id']; ?></td>
                                            <td><?php echo htmlspecialchars($payment['user_nom']); ?></td>
                                            <td><?php echo ucfirst(htmlspecialchars($payment['destination'])); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($payment['date_paiement'])); ?></td>
                                            <td><?php echo number_format($payment['prix_total'], 0, ',', ' '); ?> FCFA</td>
                                            <td><?php echo $payment['methode_paiement'] === 'reception' ? 'À la réception' : 'En ligne'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Fonction pour afficher/masquer les champs de date
        function toggleDateInputs() {
            const periode = document.getElementById('periode').value;
            const dateInputs = document.querySelectorAll('.date-input');
            
            dateInputs.forEach(input => {
                input.style.display = periode === 'personnalise' ? 'block' : 'none';
            });
        }
        
        // Graphique des statuts de paiement
        var ctxStatus = document.getElementById('paymentStatusChart').getContext('2d');
        var paymentStatusChart = new Chart(ctxStatus, {
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
        
        // Graphique des méthodes de paiement
        var ctxMethods = document.getElementById('paymentMethodsChart').getContext('2d');
        var paymentMethodsChart = new Chart(ctxMethods, {
            type: 'doughnut',
            data: {
                labels: <?php echo $methods_labels_json; ?>,
                datasets: [{
                    data: <?php echo $methods_data_json; ?>,
                    backgroundColor: <?php echo $methods_colors_json; ?>,
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
        
        // Graphique d'évolution des paiements
        var ctxEvolution = document.getElementById('paymentEvolutionChart').getContext('2d');
        var paymentEvolutionChart = new Chart(ctxEvolution, {
            type: 'line',
            data: {
                labels: <?php echo $months_labels_json; ?>,
                datasets: [{
                    label: 'Montant des paiements',
                    data: <?php echo $months_data_json; ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' FCFA';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' FCFA';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
