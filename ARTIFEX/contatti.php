<?php
// Inizializzo la sessione per gestire i dati dell'utente
session_start();
// Definisco le variabili per memorizzare i messaggi di errore e successo
$error_message = "";
$success_message = "";

// Verifico se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupero i dati dal form
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $oggetto = isset($_POST['oggetto']) ? trim($_POST['oggetto']) : '';
    $messaggio = isset($_POST['messaggio']) ? trim($_POST['messaggio']) : '';

    // Validazione dei campi
    if (empty($nome) || empty($email) || empty($messaggio)) {
        $error_message = "Per favore compila tutti i campi obbligatori";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Per favore inserisci un indirizzo email valido";
    } else {
        // Qui inseriresti il codice per salvare il messaggio nel database o inviarlo via email
        // Per ora simuliamo solo un messaggio di successo
        $success_message = "Grazie per il tuo feedback! Ti risponderemo al più presto.";

        // Reset dei campi del form dopo l'invio
        $nome = $email = $oggetto = $messaggio = "";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contatti - Artifex</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/index.css" rel="stylesheet">
    <link href="CSS/contatti.css" rel="stylesheet">

</head>
<body>
<?php include 'PageComponenti/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container hero-content">
        <h1>Contattaci</h1>
        <p class="lead">Hai suggerimenti per migliorare i nostri servizi? Scrivici!</p>
    </div>
</section>

<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="contact-form">
                <h2 class="mb-4">Inviaci il tuo feedback</h2>

                <?php if(!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if(!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nome" class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="oggetto" class="form-label">Oggetto</label>
                        <input type="text" class="form-control" id="oggetto" name="oggetto" value="<?php echo isset($oggetto) ? htmlspecialchars($oggetto) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="messaggio" class="form-label">Il tuo feedback *</label>
                        <textarea class="form-control" id="messaggio" name="messaggio" required><?php echo isset($messaggio) ? htmlspecialchars($messaggio) : ''; ?></textarea>
                        <div class="form-text">Dicci cosa possiamo migliorare</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Invia messaggio</button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="contact-info">
                <h3 class="mb-4">Informazioni di contatto</h3>

                <p><i class="fas fa-map-marker-alt"></i> Via Alcide de Gasperi, 45100 Rovigo, Italia</p>
                <p><i class="fas fa-phone"></i> +39 3312334468</p>
                <p><i class="fas fa-envelope"></i> artifex@gmail.com</p>

                <h4 class="mt-4 mb-3">Orari</h4>
                <p><i class="far fa-clock"></i> Lun - Ven: 9:00 - 18:00</p>
                <p><i class="far fa-clock"></i> Sab: 10:00 - 14:00</p>
                <p><i class="far fa-clock"></i> Dom: Chiuso</p>

                <h4 class="mt-4 mb-3">Seguici</h4>
                <div class="social-links">
                    <a href="#" class="me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in fa-lg"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'PageComponenti/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>