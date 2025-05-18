<?php
// Inizializzo la sessione per gestire i dati dell'utente
session_start();

// Verifico se l'utente è loggato
if (!isset($_SESSION['user_id'])) {
    // Utente non loggato, lo reindirizzo alla pagina di login
    header("Location: login.php");
    exit;
}

// Definisco le variabili per memorizzare i messaggi di errore e successo
$error_message = "";
$success_message = "";

// Configurazione connessione database
$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "artifex";

// Creo la connessione al database
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Verifico se la connessione è riuscita
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Recupero i dati dell'utente dal database
$user_id = $_SESSION['user_id'];
$sql = "SELECT nome, cognome, email FROM account WHERE aid = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // Utente non trovato nel database (situazione anomala)
    session_destroy();
    header("Location: login.php");
    exit;
}

// Verifico se il form per il cambio password è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verifico che tutti i campi siano stati compilati
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "Tutti i campi sono obbligatori";
    }
    // Verifico che la nuova password e la conferma coincidano
    elseif ($new_password !== $confirm_password) {
        $error_message = "La nuova password e la conferma non coincidono";
    }
    // Verifico che la nuova password sia abbastanza sicura
    elseif (strlen($new_password) < 8) {
        $error_message = "La nuova password deve contenere almeno 8 caratteri";
    }
    else {
        // Recupero la password attuale dal database
        $sql = "SELECT password FROM account WHERE aid = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();

        // Verifico che la password attuale sia corretta
        if (password_verify($current_password, $user_data['password']) || $current_password === $user_data['password']) {
            // Hash della nuova password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Aggiorno la password nel database
            $sql = "UPDATE account SET password = ? WHERE aid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $hashed_password, $user_id);

            if ($stmt->execute()) {
                $success_message = "Password aggiornata con successo!";
            } else {
                $error_message = "Errore durante l'aggiornamento della password";
            }
        } else {
            $error_message = "La password attuale non è corretta";
        }
    }
}

// Verifica se il form per l'aggiornamento dei dati utente è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $cognome = $conn->real_escape_string($_POST['cognome']);
    $email = $conn->real_escape_string($_POST['email']);

    // Verifico che tutti i campi siano stati compilati
    if (empty($nome) || empty($cognome) || empty($email)) {
        $error_message = "Tutti i campi sono obbligatori";
    } else {
        // Verifico se l'email è già utilizzata da un altro utente
        $sql = "SELECT aid FROM account WHERE email = ? AND aid != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Questa email è già in uso da un altro account";
        } else {
            // Aggiorno i dati dell'utente nel database
            $sql = "UPDATE account SET nome = ?, cognome = ?, email = ? WHERE aid = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nome, $cognome, $email, $user_id);

            if ($stmt->execute()) {
                // Aggiorno anche i dati nella sessione
                $_SESSION['nome'] = $nome;
                $_SESSION['cognome'] = $cognome;

                $success_message = "Profilo aggiornato con successo!";

                // Aggiorno i dati visualizzati
                $user['nome'] = $nome;
                $user['cognome'] = $cognome;
                $user['email'] = $email;
            } else {
                $error_message = "Errore durante l'aggiornamento del profilo";
            }
        }
    }
}

// Chiudo la connessione al database
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Mio Profilo - Artifex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/index.css" rel="stylesheet">
    <link href="CSS/profilo.css" rel="stylesheet">
</head>
<body>
<?php include 'PageComponenti/header.php'; ?>

<div class="container profile-container">
    <?php if(!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="profile-header d-flex align-items-center mb-4">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($user['nome'], 0, 1) . substr($user['cognome'], 0, 1)); ?>
        </div>
        <div>
            <h2><?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?></h2>
            <p class="text-muted mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="list-group">
                <a href="#info-profilo" class="list-group-item list-group-item-action active" id="info-tab" data-bs-toggle="list">
                    <i class="fas fa-user me-2"></i> Informazioni Profilo
                </a>
                <a href="#sicurezza" class="list-group-item list-group-item-action" id="security-tab" data-bs-toggle="list">
                    <i class="fas fa-lock me-2"></i> Sicurezza
                </a>
                <a href="prenotazioni.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-ticket-alt me-2"></i> Le mie prenotazioni
                </a>
                <a href="#" class="list-group-item list-group-item-action text-danger" data-bs-toggle="modal" data-bs-target="#logoutModal">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>

        <div class="col-md-9">
            <div class="tab-content">
                <!-- Tab Informazioni Profilo -->
                <div class="tab-pane fade show active" id="info-profilo">
                    <div class="form-container">
                        <h3 class="mb-4">Informazioni Profilo</h3>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nome" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="cognome" class="form-label">Cognome</label>
                                    <input type="text" class="form-control" id="cognome" name="cognome" value="<?php echo htmlspecialchars($user['cognome']); ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Salva modifiche
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tab Sicurezza -->
                <div class="tab-pane fade" id="sicurezza">
                    <div class="form-container">
                        <h3 class="mb-4">Cambia Password</h3>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="passwordForm">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password attuale</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nuova password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <div class="password-strength mt-2">
                                    <div id="password-strength-meter"></div>
                                </div>
                                <small class="text-muted">La password deve contenere almeno 8 caratteri</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Conferma password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <div id="password-match-message" class="form-text"></div>
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Aggiorna password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal di conferma logout -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Conferma Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Sei sicuro di voler effettuare il logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</div>

<?php include 'PageComponenti/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="Function/profilo.js"></script>
</body>
</html>