<?php
// Se non è già stata avviata una sessione, avviala
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Controlla se l'utente è loggato
$logged_in = isset($_SESSION['user_id']);
$is_admin = $logged_in && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'amministratore';

// Verifica se esiste un oggetto $db, altrimenti tentativo di connessione
if (!isset($db)) {
    require_once 'DB_CONNECT/db_config.php';
    require_once 'DB_CONNECT/functions.php';
    require_once 'DB_CONNECT/database_Connect.php';
    $config = require 'DB_CONNECT/db_config.php';
    $db = DataBase_Connect::getDB($config);
}
?>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">Artifex</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'visite.php' ? 'active' : ''; ?>" href="visite.php">Visite</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'guide.php' ? 'active' : ''; ?>" href="guide.php">Guide</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contatti.php' ? 'active' : ''; ?>" href="contatti.php">Contatti</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="carrello.php">
                                <i class="fas fa-shopping-cart"></i> Carrello
                                <?php
                                // Mostra il numero di elementi nel carrello, se presenti
                                if (isset($_SESSION['user_id'])) {
                                    $stmt = $db->prepare("SELECT SUM(quantita) as totale FROM carrello WHERE id_turista = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $result = $stmt->fetch(PDO::FETCH_OBJ);
                                    if ($result && $result->totale > 0) {
                                        echo '<span class="badge bg-danger">' . $result->totale . '</span>';
                                    }
                                }
                                ?>
                            </a>
                        </li>
                        <?php if ($is_admin): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> Account
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="profilo.php">Il mio profilo</a></li>
                                <li><a class="dropdown-item" href="prenotazioni.php">Le mie prenotazioni</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Accedi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="registrazione.php">Registrati</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

<?php if (isset($success_message) && !empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>