<?php
session_start();
include('../config/config.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Gestion de la pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$methode = isset($_GET['methode']) ? $_GET['methode'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête avec les filtres
$where_conditions = [];
$params = [];

if (!empty($statut)) {
    $where_conditions[] = "r.statut_paiement = :statut";
    $params[':statut'] = $statut;
}

if (!empty($methode)) {
    $where_conditions[] = "r.methode_paiement = :methode";
    $params[':methode'] = $methode;
}

if (!empty($date_debut)) {
    $where_conditions[] = "r.date_reservation >= :date_debut";
    $params[':date_debut'] = $date_debut;
}

if (!empty($date_fin)) {
    $where_conditions[] = "r.date_reservation <= :date_fin";
    $params[':date_fin'] = $date_fin . ' 23:59:59';
}

if (!empty($search)) {
    $where_conditions[] = "(u.nom LIKE :search OR u.email LIKE :search OR v.destination LIKE :search OR r.reference_paiement LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = " WHERE " . implode(" AND ", $where_conditions);
}

// Récupérer le nombre total de paiements pour la pagination
try {
    $count_query = "SELECT COUNT(*) as total FROM reservations r
                    LEFT JOIN users u ON r.user_id = u.id
                    LEFT JOIN vols v ON r.vol_id = v.id" . $where_clause;
    $stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_payments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_payments / $limit);
} catch (PDOException $e) {
    $error = "Erreur lors du comptage des paiements: " . $e->getMessage();
    $total_payments = 0;
    $total_pages = 1;
}

// Récupérer les paiements
try {
    $query = "SELECT r.*, v.destination, u.nom as user_nom, u.email as user_email
              FROM reservations r
              LEFT JOIN users u ON r.user_id = u.id
              LEFT JOIN vols v ON r.vol_id = v.id" . 
              $where_clause . 
              " ORDER BY r.date_reservation DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des paiements: " . $e->getMessage();
    $payments = [];
}

// Message de succès ou d'erreur
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($error) ? $error : (isset($_GET['error']) ? $_GET['error'] : '');
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Paiements - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .badge {
            font-size: 0.9rem;
            padding: 0.5em 0.8em;
        }
        
        .filter-card {
            margin-bottom: 1.5rem;
        }
        
        .action-buttons .btn {
            margin-right: 5px;
        }
        
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <?php include '../admin/top-navbar.php'; ?>
    <?php include '../admin/sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Liste des Paiements</h1>
                <div>
                    <a href="statistiques_paiements.php" class="btn btn-info">
                        <i class="bi bi-graph-up"></i> Statistiques
                    </a>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="card shadow filter-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label">Recherche</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Nom, email, destination..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="">Tous</option>
                                <option value="paye" <?php echo $statut === 'paye' ? 'selected' : ''; ?>>Payé</option>
                                <option value="en_attente" <?php echo $statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="non_paye" <?php echo $statut === 'non_paye' ? 'selected' : ''; ?>>Non payé</option>
                                <option value="annule" <?php echo $statut === 'annule' ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="methode" class="form-label">Méthode</label>
                            <select class="form-select" id="methode" name="methode">
                                <option value="">Toutes</option>
                                <option value="reception" <?php echo $methode === 'reception' ? 'selected' : ''; ?>>À la réception</option>
                                <option value="en_ligne" <?php echo $methode === 'en_ligne' ? 'selected' : ''; ?>>En ligne</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $date_debut; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $date_fin; ?>">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des paiements -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Paiements (<?php echo $total_payments; ?>)</h6>

                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Destination</th>
                                    <th>Date réservation</th>
                                    <th>Montant</th>
                                    <th>Méthode</th>
                                    <th>Statut</th>
                                    <th>Date paiement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Aucun paiement trouvé</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['id']; ?></td>
                                            <td>
                                                <a href="gerer_utilisateurs.php?action=voir&id=<?php echo $payment['user_id']; ?>" title="Voir le profil">
                                                    <?php echo htmlspecialchars($payment['user_nom']); ?>
                                                </a>
                                                <div class="small text-muted"><?php echo htmlspecialchars($payment['user_email']); ?></div>
                                            </td>
                                            <td><?php echo ucfirst(htmlspecialchars($payment['destination'])); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($payment['date_reservation'])); ?></td>
                                            <td><?php echo number_format($payment['prix_total'], 0, ',', ' '); ?> FCFA</td>
                                            <td><?php echo $payment['methode_paiement'] === 'reception' ? 'À la réception' : 'En ligne'; ?></td>
                                            <td>
                                                <?php 
                                                $status_class = '';
                                                $status_text = '';
                                                
                                                switch($payment['statut_paiement']) {
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
                                                        $status_text = $payment['statut_paiement'];
                                                }
                                                ?>
                                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($payment['date_paiement']): ?>
                                                    <?php echo date('d/m/Y H:i', strtotime($payment['date_paiement'])); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="action-buttons">
                                                <a href="gerer_reservations.php?action=voir&id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-info" title="Voir les détails">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="gerer_reservations.php?action=modifier&id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-primary" title="Modifier">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="../facture-pdf.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-success" title="Télécharger la facture" target="_blank">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <div>
                                Affichage de <?php echo min(($page - 1) * $limit + 1, $total_payments); ?> à <?php echo min($page * $limit, $total_payments); ?> sur <?php echo $total_payments; ?> entrées
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&statut=<?php echo urlencode($statut); ?>&methode=<?php echo urlencode($methode); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&search=<?php echo urlencode($search); ?>" aria-label="Précédent">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?page=1&statut=' . urlencode($statut) . '&methode=' . urlencode($methode) . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '&search=' . urlencode($search) . '">1</a></li>';
                                        if ($start_page > 2) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                    }
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&statut=' . urlencode($statut) . '&methode=' . urlencode($methode) . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '&search=' . urlencode($search) . '">' . $i . '</a></li>';
                                    }
                                    
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&statut=' . urlencode($statut) . '&methode=' . urlencode($methode) . '&date_debut=' . urlencode($date_debut) . '&date_fin=' . urlencode($date_fin) . '&search=' . urlencode($search) . '">' . $total_pages . '</a></li>';
                                    }
                                    ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&statut=<?php echo urlencode($statut); ?>&methode=<?php echo urlencode($methode); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&search=<?php echo urlencode($search); ?>" aria-label="Suivant">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
