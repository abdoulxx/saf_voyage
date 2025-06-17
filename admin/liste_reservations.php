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
$destination = isset($_GET['destination']) ? $_GET['destination'] : '';
$date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : '';
$date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : '';

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if (!empty($statut)) {
    $where_conditions[] = "r.statut_paiement = :statut";
    $params[':statut'] = $statut;
}

if (!empty($destination)) {
    $where_conditions[] = "v.destination LIKE :destination";
    $params[':destination'] = "%$destination%";
}

if (!empty($date_debut)) {
    $where_conditions[] = "r.date_reservation >= :date_debut";
    $params[':date_debut'] = $date_debut . ' 00:00:00';
}

if (!empty($date_fin)) {
    $where_conditions[] = "r.date_reservation <= :date_fin";
    $params[':date_fin'] = $date_fin . ' 23:59:59';
}

$where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : "";

// Récupérer le nombre total de réservations pour la pagination
try {
    $count_query = "SELECT COUNT(*) as total FROM reservations r
                   LEFT JOIN vols v ON r.vol_id = v.id" . $where_clause;
    $stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total_reservations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_reservations / $limit);
} catch (PDOException $e) {
    $error = "Erreur lors du comptage des réservations: " . $e->getMessage();
    $total_reservations = 0;
    $total_pages = 1;
}

// Récupérer les réservations
try {
    $query = "SELECT r.*, v.destination, v.date_depart, v.date_retour, 
              h.nom as hotel_nom, u.nom as user_nom, u.email as user_email
              FROM reservations r
              LEFT JOIN vols v ON r.vol_id = v.id
              LEFT JOIN hotels h ON r.hotel_id = h.id
              LEFT JOIN users u ON r.user_id = u.id" . $where_clause . "
              ORDER BY r.date_reservation DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des réservations: " . $e->getMessage();
    $reservations = [];
}

// Récupérer les destinations pour le filtre
try {
    $query = "SELECT DISTINCT destination FROM vols ORDER BY destination";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $destinations = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $destinations = [];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Réservations - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .filters-card {
            margin-bottom: 20px;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .pagination {
            justify-content: center;
        }
        
        .badge {
            font-size: 0.8rem;
            padding: 0.4em 0.6em;
        }
    </style>
</head>

<body>
    <?php include '../admin/top-navbar.php'; ?>
    <?php include '../admin/sidebar.php'; ?>

    <div id="content">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Liste des Réservations</h1>
               
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="card shadow mb-4 filters-card">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
                </div>
                <div class="card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select" id="statut" name="statut">
                                <option value="">Tous</option>
                                <option value="paye" <?php echo $statut === 'paye' ? 'selected' : ''; ?>>Payé</option>
                                <option value="en_attente" <?php echo $statut === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="non_paye" <?php echo $statut === 'non_paye' ? 'selected' : ''; ?>>Non payé</option>
                                <option value="annule" <?php echo $statut === 'annule' ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="destination" class="form-label">Destination</label>
                            <select class="form-select" id="destination" name="destination">
                                <option value="">Toutes</option>
                                <?php foreach ($destinations as $dest): ?>
                                    <option value="<?php echo $dest; ?>" <?php echo $destination === $dest ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($dest); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_debut" class="form-label">Date début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $date_debut; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_fin" class="form-label">Date fin</label>
                            <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $date_fin; ?>">
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-filter"></i> Filtrer
                            </button>
                            <a href="liste_reservations.php" class="btn btn-secondary">Réinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Liste des réservations -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Réservations (<?php echo $total_reservations; ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Destination</th>
                                    <th>Dates</th>
                                    <th>Hôtel</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th>Date réservation</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservations)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Aucune réservation trouvée</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <tr>
                                            <td><?php echo $reservation['id']; ?></td>
                                            <td>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($reservation['user_nom']); ?></div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($reservation['user_email']); ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo ucfirst(htmlspecialchars($reservation['destination'])); ?></td>
                                            <td>
                                                <div>Du <?php echo date('d/m/Y', strtotime($reservation['date_depart'])); ?></div>
                                                <div>Au <?php echo date('d/m/Y', strtotime($reservation['date_retour'])); ?></div>
                                            </td>
                                            <td><?php echo $reservation['hotel_id'] ? htmlspecialchars($reservation['hotel_nom']) : 'Aucun'; ?></td>
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
                                                <div class="small mt-1">
                                                    <?php echo $reservation['methode_paiement'] === 'reception' ? 'À la réception' : 'En ligne'; ?>
                                                </div>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($reservation['date_reservation'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="gerer_reservations.php?action=voir&id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="gerer_reservations.php?action=modifier&id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="../facture-pdf.php?id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-success" target="_blank">
                                                        <i class="bi bi-file-pdf"></i>
                                                    </a>
                                                    <a href="gerer_reservations.php?action=supprimer&id=<?php echo $reservation['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" aria-label="Précédent">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : ''; ?>" aria-label="Suivant">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
