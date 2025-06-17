<?php
session_start();
include('../config/config.php');

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// Initialiser les variables
$action = isset($_GET['action']) ? $_GET['action'] : '';
$message_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = [];
$error = '';
$success = '';

// Traitement des actions
if ($action === 'supprimer' && $message_id > 0) {
    try {
        $query = "DELETE FROM messages_contact WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $message_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $success = "Le message a été supprimé avec succès.";
        header("Location: gerer_messages.php?success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

// Récupérer les informations du message pour visualisation
if ($action === 'voir' && $message_id > 0) {
    try {
        $query = "SELECT * FROM messages_contact WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $message_id, PDO::PARAM_INT);
        $stmt->execute();
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$message) {
            $error = "Message introuvable.";
            $action = '';
        }
    } catch (PDOException $e) {
        $error = "Erreur lors de la récupération des informations: " . $e->getMessage();
        $action = '';
    }
}

// Gestion de la pagination pour la liste des messages
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Recherche de messages
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = '';
$params = [];

if (!empty($search)) {
    $search_condition = " WHERE nom LIKE :search OR email LIKE :search OR message LIKE :search";
    $params[':search'] = "%$search%";
}

// Récupérer le nombre total de messages pour la pagination
try {
    $count_query = "SELECT COUNT(*) as total FROM messages_contact" . $search_condition;
    $stmt = $db->prepare($count_query);
    if (!empty($search)) {
        $stmt->bindParam(':search', $params[':search']);
    }
    $stmt->execute();
    $total_messages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_messages / $limit);
} catch (PDOException $e) {
    $error = "Erreur lors du comptage des messages: " . $e->getMessage();
    $total_messages = 0;
    $total_pages = 1;
}

// Récupérer les messages
try {
    $query = "SELECT * FROM messages_contact" . $search_condition . " ORDER BY date_envoi DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    if (!empty($search)) {
        $stmt->bindParam(':search', $params[':search']);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des messages: " . $e->getMessage();
    $messages = [];
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
    <title>Gestion des Messages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .message-card {
            transition: all 0.3s ease;
        }
        
        .message-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .message-preview {
            max-height: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .message-content {
            white-space: pre-wrap;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
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
            <?php if ($action === 'voir' && !empty($message)): ?>
                <!-- Affichage détaillé d'un message -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Détails du message</h1>
                    <a href="gerer_messages.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Retour à la liste
                    </a>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Message de <?php echo htmlspecialchars($message['nom']); ?></h6>
                        <span class="text-muted"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nom:</strong> <?php echo htmlspecialchars($message['nom']); ?></p>
                                <p><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>"><?php echo htmlspecialchars($message['email']); ?></a></p>
                                <p><strong>Date d'envoi:</strong> <?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <a href="mailto:<?php echo htmlspecialchars($message['email']); ?>" class="btn btn-primary">
                                    <i class="bi bi-reply"></i> Répondre par email
                                </a>
                                <a href="gerer_messages.php?action=supprimer&id=<?php echo $message['id']; ?>" class="btn btn-danger ms-2" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                    <i class="bi bi-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                        <hr>
                        <h6 class="font-weight-bold">Message:</h6>
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Liste des messages -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Gestion des Messages</h1>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Filtres -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recherche</h6>
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-10">
                                <input type="text" class="form-control" id="search" name="search" placeholder="Rechercher par nom, email ou contenu du message..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-search"></i> Rechercher
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des messages -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Messages (<?php echo $total_messages; ?>)</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($messages)): ?>
                            <div class="alert alert-info">Aucun message trouvé.</div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($messages as $msg): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card message-card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($msg['nom']); ?></strong>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($msg['email']); ?></div>
                                                </div>
                                                <span class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?></span>
                                            </div>
                                            <div class="card-body">
                                                <div class="message-preview">
                                                    <?php echo htmlspecialchars(substr($msg['message'], 0, 150)); ?>
                                                    <?php if (strlen($msg['message']) > 150): ?>...<?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent d-flex justify-content-between">
                                                <a href="gerer_messages.php?action=voir&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i> Voir le message
                                                </a>
                                                <a href="gerer_messages.php?action=supprimer&id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message ?');">
                                                    <i class="bi bi-trash"></i> Supprimer
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="pagination-container">
                                    <div>
                                        Affichage de <?php echo min(($page - 1) * $limit + 1, $total_messages); ?> à <?php echo min($page * $limit, $total_messages); ?> sur <?php echo $total_messages; ?> messages
                                    </div>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination">
                                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Précédent">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                            
                                            <?php
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            
                                            if ($start_page > 1) {
                                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                                                if ($start_page > 2) {
                                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                                }
                                            }
                                            
                                            for ($i = $start_page; $i <= $end_page; $i++) {
                                                echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '">' . $i . '</a></li>';
                                            }
                                            
                                            if ($end_page < $total_pages) {
                                                if ($end_page < $total_pages - 1) {
                                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                                }
                                                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '&search=' . urlencode($search) . '">' . $total_pages . '</a></li>';
                                            }
                                            ?>
                                            
                                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Suivant">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
