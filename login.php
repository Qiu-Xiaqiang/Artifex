<?php

// Inizializzo la sessione per gestire i dati dell'utente
session_start();
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
include 'PageComponenti/header.php';
// Verifico se la connessione è riuscita
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Verifico se l'utente è già loggato
if (isset($_SESSION['user_id'])) {
    // Utente già loggato, lo reindirizzo alla home
    header("Location: index.php");
    exit;
}
// Verifico se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupero i dati dal form
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    // Verifico se i campi sono stati compilati
    if (empty($email) || empty($password)) {
        $error_message = "Inserisci email e password";
    } else {
        // Cerco l'utente nel database
        $sql = "SELECT aid, nome, cognome, password, id_tipologia FROM account WHERE email = '$email'";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verifico la password
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // Password corretta, creo la sessione
                $_SESSION['user_id'] = $user['aid'];
                $_SESSION['nome'] = $user['nome'];
                $_SESSION['cognome'] = $user['cognome'];
                $_SESSION['tipo_account'] = $user['id_tipologia'];

                // Messaggio di successo
                $success_message = "Accesso a Artifex con successo!";

                // Reindirizzo alla home dopo 2 secondi
                header("refresh:2; url=index.php");
            } else {
                $error_message = "Password non valida";
            }
        } else {
            $error_message = "Email non trovata";
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
    <title>Accedi - Artifex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="CSS/index.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .container {
            flex: 1;
        }

        .login-container {
            max-width: 450px;
            margin: 80px auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 15px;
        }
        .success-message {
            color: #198754;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<!-- Contenuto pagina -->
<div class="container">
    <div class="login-container">
        <h2 class="mb-4 text-center">Accedi ad Artifex</h2>

        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember">
                <label class="form-check-label" for="remember">Ricordami</label>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Accedi</button>
            </div>

            <div class="mt-3 text-center">
                <p>Non hai un account? <a href="registrazione.php">Registrati qui</a></p>
                <p><a href="#">Password dimenticata?</a></p>
            </div>
        </form>
    </div>
</div>
<?php include 'PageComponenti/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>