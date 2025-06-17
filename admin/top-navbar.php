<!-- top-navbar.php -->
<nav id="top-navbar">
    <div class="navbar-brand d-flex align-items-center">
        <img src="logo.jpg" alt="Logo" style="height: 40px; margin-right: 10px;">
        <span>Dashboard Admin - Saf Voyage</span>
    </div>
    <div class="user-info">
        <div class="d-flex align-items-center">
            <!-- Messages Dropdown -->
            <div class="dropdown me-3 position-relative">
                <a href="#" class="text-decoration-none text-white" id="messagesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-envelope fs-5"></i>
                    <?php
                    // Compter les messages non lus
                    try {
                        $query = "SELECT COUNT(*) as count FROM messages_contact";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $messages_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    } catch (PDOException $e) {
                        $messages_count = 0;
                    }
                    
                    if ($messages_count > 0):
                    ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; transform: translate(-50%, -50%);">
                        <?php echo $messages_count; ?>
                    </span>
                    <?php endif; ?>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="messagesDropdown" style="min-width: 280px;">
                    <h6 class="dropdown-header">Messages</h6>
                    <?php
                    // Récupérer les derniers messages
                    try {
                        $query = "SELECT * FROM messages_contact ORDER BY date_envoi DESC LIMIT 3";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        $recent_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $recent_messages = [];
                    }
                    
                    if (empty($recent_messages)):
                    ?>
                    <a class="dropdown-item text-center small text-gray-500" href="#">Aucun message</a>
                    <?php else: 
                        foreach ($recent_messages as $msg):
                    ?>
                    <a class="dropdown-item d-flex align-items-center" href="gerer_messages.php?action=voir&id=<?php echo $msg['id']; ?>">
                        <div>
                            <div class="text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars(substr($msg['message'], 0, 60)); ?><?php if (strlen($msg['message']) > 60): ?>...<?php endif; ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($msg['nom']); ?> · <?php echo date('d/m/Y', strtotime($msg['date_envoi'])); ?></div>
                        </div>
                    </a>
                    <?php endforeach; endif; ?>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-center small" href="gerer_messages.php">Voir tous les messages</a>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-decoration-none text-white dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="me-2">
                        <?php echo isset($_SESSION['admin_nom']) ? htmlspecialchars($_SESSION['admin_nom']) : 'Administrateur'; ?>
                    </span>
                    <i class="bi bi-person-circle"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="parametres.php">
                            <i class="bi bi-gear me-2"></i> Paramètres
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
