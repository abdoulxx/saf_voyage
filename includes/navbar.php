<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<nav class="navbar navbar-expand-lg navbar-light bg-white">
    <!-- Logo à gauche -->
    <a class="navbar-brand" href="/saf_voyage">
        <img src="assets/images/logo.jpg" alt="" width="120">
    </a>

    <!-- Bouton Burger pour mobile, caché en mode desktop -->
    <a href="#" id="openBtn" class="d-lg-none ms-auto">
        <span class="burger-icon">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </a>

    <!-- Liens du menu, visibles en mode desktop (à gauche à côté du logo) -->
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-3"> <!-- Margin-start to space out from the logo -->
            <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="/saf_voyage">Accueil</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="services.php">Services</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="a_propos.php">À propos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="contact.php">Contact</a>
            </li>
        </ul>

        <!-- Menu utilisateur à droite en desktop -->
        <ul class="navbar-nav ms-auto">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/saf_voyage/login.php">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="profil.php">
                        <i class="fas fa-user-circle"></i> Mon profil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="mes_reservations.php">
                        <i class="fas fa-box"></i> Mes reservations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="deconnexion.php">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- Sidenav menu, visible en mode mobile -->
<div id="mySidenav" class="sidenav">
    <a class="navbar-brand" href="/saf_voyage">
        <img src="assets/images/logo.jpg" alt="" width="120">
    </a>
    <a id="closeBtn" href="#" class="close">×</a>
    <ul>
        <li><a href="/saf_voyage"><i class="fas fa-home"></i> Accueil</a></li>
        <li><a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a></li>
        <li><a href="apropos.php"><i class="fas fa-info-circle"></i> À propos</a></li>
        <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <li><a href="/hotel/login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a></li>
        <?php else: ?>
            <li><a href="profil.php"><i class="fas fa-user-circle"></i> Mon profil</a></li>
            <li><a href="mes_commandes.php"><i class="fas fa-box"></i> Mes reservtion</a></li>
            <li><a href="deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        <?php endif; ?>
    </ul>
</div>

<style>
    .navbar .nav-link, .sidenav a {
        color: #000000; /* Couleur noire pour les liens */
        font-family: 'Poppins', sans-serif;
        font-size: 16px;
    }

    .sidenav a:hover, .navbar .nav-link:hover {
        color: rgb(10, 31, 122); /* Changement de couleur au survol */
    }

    .navbar-brand {
        margin-right: 10px; /* Espacement entre le logo et le premier lien */
    }

    /* Sidenav style */
    .sidenav .close {
        color: #000000; /* Couleur noire pour le bouton fermer */
    }

    /* Mobile burger menu icon */
    .burger-icon {
        display: block;
        width: 30px;
        height: 30px;
        position: relative;
        margin-left: -45px;
    }

    .burger-icon span {
        display: block;
        width: 100%;
        height: 4px;
        background-color: rgb(10, 31, 122);
        margin-bottom: 5px;
        transition: all 0.3s ease-in-out;
    }
    @media (max-width: 768px) {
    .navbar-brand {
        margin-left: -20px; /* Ajustez cette valeur pour déplacer le logo à gauche */
    }
    
}
</style>
