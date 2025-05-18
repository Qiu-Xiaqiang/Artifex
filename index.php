<?php
// Connessione al database indirizzando alla mia cartella DB_CONNECT
require_once 'DB_CONNECT/db_config.php';
require_once 'DB_CONNECT/functions.php';
require_once 'DB_CONNECT/database_Connect.php';
$config = require 'DB_CONNECT/db_config.php';
$db = DataBase_Connect::getDB($config);

// Query per ottenere le località principali - Spostata qui perché ora questa sezione viene prima
$stmt = $db->prepare("SELECT si.luogo, COUNT(*) as total, 
                     (SELECT sid FROM siti_interesse WHERE luogo = si.luogo LIMIT 1) as id
                     FROM siti_interesse si
                     JOIN visite_siti vs ON si.sid = vs.id_sito 
                     JOIN visite v ON vs.id_visita = v.vid
                     GROUP BY si.luogo 
                     ORDER BY total DESC 
                     LIMIT 4");
$stmt->execute();
$locations = $stmt->fetchAll(PDO::FETCH_OBJ);

// Controlla se l'utente è loggato
session_start();
$logged_in = isset($_SESSION['user_id']);
$is_admin = $logged_in && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'amministratore';

// Controlla se c'è un messaggio di successo nella sessione
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Rimuovi il messaggio dopo l'uso
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artifex - Visite Guidate</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/index.css" rel="stylesheet">
</head>
<body>
<?php include 'PageComponenti/header.php'; ?>

<!-- Hero Section -->
<section class="hero" style="background-image: url('Immagini/home.jpg');">
    <div class="container hero-content text-center">
        <h1 class="fade-in">Scopri l'Arte e la Storia con Artifex</h1>
        <p class="lead mb-4 fade-in delay-1">Visite guidate esclusive nei più importanti siti culturali in Italia</p>
        <div class="fade-in delay-2">
            <a href="visite.php" class="btn btn-primary btn-lg me-2">Esplora le Visite</a>
            <?php if (!$logged_in): ?>
                <a href="registrazione.php" class="btn btn-outline-light btn-lg">Registrati Ora</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Locations Section - SPOSTATA SOPRA COME RICHIESTO -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Destinazioni Principali</h2>
        <div class="row">
            <?php
            // Immagini per le città italiane più famose
            $cityImages = [
                'Roma' => 'https://tourismmedia.italia.it/is/image/mitur/20220127150143-colosseo-roma-lazio-shutterstock-756032350?wid=1600&hei=900&fit=constrain,1&fmt=webp',
                'Firenze' => 'https://www.viaggiareunostiledivita.it/wp-content/uploads/2022/01/Copertina-1.jpg',
                'Venezia' => 'https://www.italia.it/content/dam/tdh/it/site1/getty-temp-image/category-nuove/venezia/1600X900_venezia_san_marco_gabbiano.jpg',
                'Milano' => 'https://www.lombardia.info/wp-content/uploads/sites/112/milano-hd.jpg',
                'Napoli' => 'https://www.partenopeintour.com/wp-content/uploads/2024/09/napoli-notte-hd-1600x600.jpg',
                'Pisa' => 'https://static2-viaggi.corriereobjects.it/wp-content/uploads/2015/06/pisa-getty.jpg?v=1437148777',
                'Torino' => 'https://blog.italotreno.com/wp-content/uploads/2022/03/Torino-iStock-940619078-1140x660.jpg',
                'Bologna' => 'https://www.italieonline.eu/user/blogimg/leto/emilia-romagna/bologna-uvod.jpg',
                'Genova' => 'https://www.portsofgenoa.com/images/magazine/NOTIZIE_Imm/Genova_ph_merlofotografia_220228-0421_RID.webp',
                'Verona' => 'https://www.veneto.info/wp-content/uploads/sites/114/verona-hd.jpg',
                'Siena' => 'https://tourismmedia.italia.it/is/image/mitur/20210311182031-enit-siena?wid=800&hei=500&fit=constrain,1&fmt=webp',
                'Palermo' => 'https://webassets.transavia.com/78ae936f-d39d-01b0-c3ef-dc738304142f/39d786c3-bf4e-4bcc-89c3-c743b29b97b8/Cefalu%20%28Sicily%29.jpg'
            ];

            // Immagine di fallback per città non presenti nell'array
            $fallbackImage = 'https://www.lafinestraaccanto.com/wp-content/uploads/2024/09/Alberobello-in-evidenza.jpg';

            foreach ($locations as $location):
                $imgSrc = isset($cityImages[$location->luogo]) ?
                    $cityImages[$location->luogo] :
                    $fallbackImage;
                ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="card location-card">
                        <img src="<?php echo $imgSrc; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($location->luogo); ?>">
                        <div class="card-body text-center">
                            <h5 class="card-title"><?php echo htmlspecialchars($location->luogo); ?></h5>
                            <p class="text-muted"><?php echo $location->total; ?> visite disponibili</p>
                            <a href="visite.php?luogo=<?php echo urlencode($location->luogo); ?>" class="btn btn-sm btn-outline-primary">Esplora</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Info Section -->
<section class="info-section py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="info-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h3 class="info-title">Siti Esclusivi</h3>
                <p>Accedi ai luoghi più affascinanti e significativi del patrimonio culturale italiano.</p>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="info-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <h3 class="info-title">Guide Esperte</h3>
                <p>Visite condotte da professionisti qualificati in più lingue per un'esperienza indimenticabile.</p>
            </div>
            <div class="col-md-4">
                <div class="info-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3 class="info-title">Prenotazione Facile</h3>
                <p>Prenota più visite con un solo click e ricevi i biglietti direttamente nella tua email.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Cosa Dicono di Noi</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"Un'esperienza indimenticabile! La nostra guida, Marco, era incredibilmente preparata e ha reso la visita al Colosseo un'avventura affascinante."</p>
                    <div class="testimonial-author">
                        <p class="testimonial-name">Prof. Emiliano Spiller.</p>
                        <p class="testimonial-tour">Tour Classico del Colosseo</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"Prenotazione semplice, organizzazione impeccabile e guide straordinarie. Ho già prenotato altre due visite per il mio prossimo viaggio!"</p>
                    <div class="testimonial-author">
                        <p class="testimonial-name">Prof.Gasparini Filippo.</p>
                        <p class="testimonial-tour">Firenze Rinascimentale</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="testimonial-text">"Abbiamo scelto Artifex per il nostro viaggio di famiglia a Venezia e non potevamo fare scelta migliore. Il tour delle isole è stato perfetto!"</p>
                    <div class="testimonial-author">
                        <p class="testimonial-name">Prof.ssa Romagnolo Sara.</p>
                        <p class="testimonial-tour">Venezia e le sue Isole</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="mb-4">Pronto a scoprire la bellezza dell'arte e della storia?</h2>
        <p class="lead mb-4">Registrati ora e inizia il tuo viaggio culturale con Artifex</p>
        <?php if (!$logged_in): ?>
            <a href="registrazione.php" class="btn btn-primary btn-lg">Crea un Account</a>
        <?php else: ?>
            <a href="visite.php" class="btn btn-primary btn-lg">Esplora le Visite</a>
        <?php endif; ?>
    </div>
</section>

<?php include 'PageComponenti/footer.php'; ?>
</body>
</html>