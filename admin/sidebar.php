<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<nav id="sidebar">
    <ul>
        
        <li><a href="dashboard.php" class="nav-link active">Dashboard <i class="bi bi-speedometer2"></i></a></li>

        <!-- Gestion des utilisateurs -->
        <li>
            <a href="#utilisateursMenu" class="nav-link" data-bs-toggle="collapse">
                <i class="bi bi-person-fill"></i> Utilisateurs <i class="bi bi-caret-down-fill"></i>
            </a>
            <div class="collapse" id="utilisateursMenu">
                <div class="dropdown-container">
                    <a href="liste_utilisateurs.php">Liste des utilisateurs</a>
                </div>
            </div>
        </li>

        <!-- Gestion des réservations -->
        <li>
            <a href="#reservationsMenu" class="nav-link" data-bs-toggle="collapse">
                <i class="bi bi-calendar-check"></i> Réservations <i class="bi bi-caret-down-fill"></i>
            </a>
            <div class="collapse" id="reservationsMenu">
                <div class="dropdown-container">
                    <a href="liste_reservations.php">Liste des réservations</a>
                </div>
            </div>
        </li>

        <li>
            <a href="#volsmenu" class="nav-link" data-bs-toggle="collapse">
                <i class="bi bi-house-door"></i> vols <i class="bi bi-caret-down-fill"></i>
            </a>
            <div class="collapse" id="volsmenu">
                <div class="dropdown-container">
                    <a href="ajouter-vol.php">Ajouter</a>
                    <a href="liste-vols.php">Liste des vols</a>

                </div>
            </div>
        </li>

        <!-- Gestion des paiements -->
        <li>
            <a href="#paiementsMenu" class="nav-link" data-bs-toggle="collapse">
                <i class="bi bi-credit-card"></i> Paiements <i class="bi bi-caret-down-fill"></i>
            </a>
            <div class="collapse" id="paiementsMenu">
                <div class="dropdown-container">
                    <a href="liste_paiements.php">Liste des paiements</a>
                    <a href="statistiques_paiements.php">Statistiques</a>
                </div>
            </div>
        </li>

        <!-- Gestion des messages -->
        <li>
            <a href="#messagesMenu" class="nav-link" data-bs-toggle="collapse">
                <i class="bi bi-chat-dots"></i> Messages <i class="bi bi-caret-down-fill"></i>
            </a>
            <div class="collapse" id="messagesMenu">
                <div class="dropdown-container">
                    <a href="gerer_messages.php">Gérer les messages</a>
                </div>
            </div>
        </li>
    </ul>
</nav>


<style>
    .logo {
        position: relative;
        top: 10px;
        left: 10px;
    }
</style>
<style>
    body {
        display: flex;
        min-height: 100vh;
        background-color: #f5f7fa;
        font-family: 'Poppins', sans-serif;
        margin: 0;
    }

    /* Navbar du haut */
    #top-navbar {
        width: 100%;
        height: 8%;
        background-color:rgb(1, 27, 51);
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

    #top-navbar .navbar-brand {
        font-size: 1.5em;
        font-weight: bold;
        color:rgb(255, 255, 255);
    }

    #top-navbar .user-info {
        display: flex;
        align-items: center;
    }


    /* Sidebar */
    #sidebar {
        width: 250px;
        height: 100vh;
        background-color:rgb(1, 27, 51);
        position: fixed;
        top: 60px;
        left: 0;
        padding-top: 20px;
        overflow-y: auto;
    }

    #sidebar ul {
        padding: 0;
        list-style: none;
    }

    #sidebar ul li {
        width: 100%;
    }

    #sidebar ul li a {
        display: block;
        padding: 15px;
        color: #ffffff;
        text-decoration: none;
        font-size: 1.1em;
        transition: 0.3s;
    }

    #sidebar ul li a:hover {
        background-color: rgb(10, 31, 122);
    }

    #sidebar ul li a.active {
        background-color:rgb(243, 243, 243);
        color: #343a40;
    }

    /* Dropdown style */
    .dropdown-container {
        background-color: #2c3e50;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .dropdown-container h6 {
        font-size: 1.1em;
        margin-bottom: 10px;
        color: #343a40;
    }

    .dropdown-container a {
        display: block;
        padding: 10px 0;
        font-size: 1em;
        color: #343a40;
    }

    .dropdown-container a:hover {
        text-decoration: underline;
    }

    /* Icon with text alignment */
    .nav-link i {
        margin-right: 10px;
    }

    /* Content Area */
    #content {
        margin-left: 250px;
        padding: 80px 20px 20px 20px;
        width: 100%;
        background-color: #f5f7fa;
    }

    .page-content {
        margin-top: 20px;
    }
</style>