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
include 'PageComponenti/header.php';
// Creo la connessione al database
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
// Verifico se la connessione è riuscita
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
// Recupero le lingue dal database per il menu a tendina
$sql_lingue = "SELECT lid, lingua FROM lingue ORDER BY lingua";
$result_lingue = $conn->query($sql_lingue);
$lingue = [];
if ($result_lingue->num_rows > 0) {
    while($row = $result_lingue->fetch_assoc()) {
        $lingue[] = $row;
    }
}

// Verifico se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupero i dati dal form
    $nome = $conn->real_escape_string($_POST['nome']);
    $cognome = $conn->real_escape_string($_POST['cognome']);
    $nazionalita = $conn->real_escape_string($_POST['nazionalita']);
    $id_lingua = $conn->real_escape_string($_POST['lingua']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefono = $conn->real_escape_string($_POST['telefono']);
    $password = $_POST['password'];
    $conferma_password = $_POST['conferma_password'];

    // Validazione dei dati
    $is_valid = true;

    if (empty($nome) || empty($cognome) || empty($nazionalita) || empty($email) || empty($password)) {
        $error_message = "Tutti i campi contrassegnati con * sono obbligatori";
        $is_valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Formato email non valido";
        $is_valid = false;
    } elseif ($password !== $conferma_password) {
        $error_message = "Le password non corrispondono";
        $is_valid = false;
    } elseif (strlen($password) < 8) {
        $error_message = "La password deve contenere almeno 8 caratteri";
        $is_valid = false;
    }

    // Verifico se l'email è già registrata
    $check_email = "SELECT * FROM account WHERE email = '$email'";
    $result = $conn->query($check_email);
    if ($result->num_rows > 0) {
        $error_message = "Email già registrata. Prova con un'altra email o effettua l'accesso";
        $is_valid = false;
    }

    // Se i dati sono validi, inserisco nel database
    if ($is_valid) {
        // Hash della password per sicurezza
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Preparo la query per inserire il nuovo account
        $sql = "INSERT INTO account (nome, cognome, email, password, telefono, id_lingua, id_tipologia) 
                VALUES ('$nome', '$cognome', '$email', '$hashed_password', '$telefono', '$id_lingua', 1)";

        if ($conn->query($sql) === TRUE) {
            $success_message = "Registrazione effettuata con successo! Ora puoi accedere.";
            // Reindirizzo alla pagina di login
            header("refresh:2; url=login.php");
        } else {
            $error_message = "Errore durante la registrazione: " . $conn->error;
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
    <title>Registrazione - Artifex</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="CSS/index.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
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

<div class="container">
    <div class="form-container">
        <h2 class="mb-4 text-center">Registrazione Account Artifex</h2>

        <?php if(!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if(!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nome" class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="nome" name="nome" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cognome" class="form-label">Cognome *</label>
                    <input type="text" class="form-control" id="cognome" name="cognome" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="nazionalita" class="form-label">Nazionalità *</label>
                <input type="text" class="form-control" id="nazionalita" name="nazionalita" required>
            </div>

            <div class="mb-3">
                <label for="lingua" class="form-label">Lingua preferita *</label>
                <select class="form-select" id="lingua" name="lingua" required>
                    <option value="" selected disabled>Seleziona una lingua</option>
                    <?php foreach($lingue as $lingua): ?>
                        <option value="<?php echo $lingua['lid']; ?>"><?php echo $lingua['lingua']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>

            <div class="mb-3">
                <label for="telefono" class="form-label">Telefono</label>
                <input type="tel" class="form-control" id="telefono" name="telefono">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password *</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <small class="text-muted">Minimo 8 caratteri</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="conferma_password" class="form-label">Conferma Password *</label>
                    <input type="password" class="form-control" id="conferma_password" name="conferma_password" required>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="privacy" required>
                <label class="form-check-label" for="privacy">Accetto i termini e le condizioni sulla privacy *</label>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Registrati</button>
            </div>

            <div class="mt-3 text-center">
                <p>Hai già un account? <a href="login.php">Accedi qui</a></p>
            </div>
        </form>
    </div>
</div>
<?php include 'PageComponenti/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>