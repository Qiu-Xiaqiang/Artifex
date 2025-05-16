<?php
// Connessione al database indirizzando alla mia cartella DB_CONNECT
require_once 'DB_CONNECT/db_config.php';
require_once 'DB_CONNECT/functions.php';
require_once 'DB_CONNECT/database_Connect.php';
$config = require 'DB_CONNECT/db_config.php';
$db = DataBase_Connect::getDB($config);

// Query per ottenere le visite attive con prezzi minimi e conteggio eventi disponibili
$stmt = $db->prepare("SELECT v.vid AS id, v.titolo, v.descrizione, v.durata_media,
                      (SELECT MIN(e.prezzo) FROM eventi e WHERE e.id_visita = v.vid AND e.inizio >= CURDATE()) as prezzo_minimo,
                      (SELECT COUNT(*) FROM eventi e WHERE e.id_visita = v.vid AND e.inizio >= CURDATE()) as eventi_disponibili,
                      (SELECT si.luogo FROM visite_siti vs 
                       JOIN siti_interesse si ON vs.id_sito = si.sid 
                       WHERE vs.id_visita = v.vid 
                       LIMIT 1) as luogo,
                      'cinqueterre.jpg' as immagine
                      FROM visite v 
                      WHERE v.vid IN (SELECT DISTINCT id_visita FROM eventi WHERE inizio >= CURDATE())
                      ORDER BY eventi_disponibili DESC, v.titolo ASC");
$stmt->execute();
$visite = $stmt->fetchAll(PDO::FETCH_OBJ);

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
                    <a class="nav-link active" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="visite.php">Visite</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="guide.php">Guide</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="contatti.php">Contatti</a>
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

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

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

<!-- Featured Tours Section -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Visite in Evidenza</h2>

        <div class="row">
            <?php
            $counter = 0;
            foreach ($visite as $visita):
                // Mostra solo i primi 6 tour
                if ($counter >= 6) break;

                // Salta le visite senza eventi programmati
                if ($visita->eventi_disponibili <= 0) continue;

                $counter++;
                // Array delle immagini di alta qualità per le visite in evidenza
                $featuredImages = [
                    "https://www.lafinestraaccanto.com/wp-content/uploads/2024/09/Alberobello-in-evidenza.jpg",
                    "https://images.unsplash.com/photo-1525874684015-58379d421a52?q=80&w=1000",
                    "https://images.unsplash.com/photo-1516483638261-f4dbaf036963?q=80&w=1000",
                    "https://images.unsplash.com/photo-1498307833015-e7b400441eb8?q=80&w=1000",
                    "https://images.unsplash.com/photo-1499678329028-101435549a4e?q=80&w=1000",
                    "https://images.unsplash.com/photo-1515859005217-8a1f08870f59?q=80&w=1000",
                    "https://images.unsplash.com/photo-1529260830199-42c24126f198?q=80&w=1000",
                    "https://images.unsplash.com/photo-1476362174823-3a23f4aa6d76?q=80&w=1000"
                ];

                // Seleziona un'immagine in base all'ID (per variare le immagini)
                $imageIndex = $visita->id % count($featuredImages);
                $imagePath = $featuredImages[$imageIndex];

                // Verifica se esiste un'immagine personalizzata
                if (file_exists("uploads/visite/" . $visita->id . ".jpg")) {
                    $imagePath = "uploads/visite/" . $visita->id . ".jpg";
                }
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 tour-card">
                        <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($visita->titolo); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($visita->titolo); ?></h5>
                            <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($visita->luogo); ?></span>
                            <p class="text-muted small"><i class="far fa-clock me-1"></i> Durata: <?php echo htmlspecialchars($visita->durata_media); ?> minuti</p>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($visita->descrizione, 0, 150)) . (strlen($visita->descrizione) > 150 ? '...' : '')); ?></p>
                            <?php if ($visita->prezzo_minimo): ?>
                                <p class="card-price">A partire da €<?php echo number_format($visita->prezzo_minimo, 2, ',', '.'); ?></p>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <?php if ($visita->eventi_disponibili > 0): ?>
                                    <span class="badge bg-success"><?php echo $visita->eventi_disponibili; ?> eventi disponibili</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nessun evento disponibile</span>
                                <?php endif; ?>
                                <a href="dettaglio_visita.php?id=<?php echo $visita->id; ?>" class="btn btn-sm btn-outline-primary">Dettagli</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($counter === 0): ?>
                <div class="col-12 text-center">
                    <p>Non ci sono visite disponibili al momento.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-4">
            <a href="visite.php" class="btn btn-primary">Vedi tutte le visite</a>
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

<!-- Featured Locations Section -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Destinazioni Principali</h2>
        <div class="row">
            <?php
            // Query per ottenere le località principali
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

            // Immagini specifiche per le città italiane più famose
            $cityImages = [
                'Roma' => 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?q=80&w=1000',
                'Firenze' => 'https://www.viaggiareunostiledivita.it/wp-content/uploads/2022/01/Copertina-1.jpg',
                'Venezia' => 'https://images.unsplash.com/photo-1514890547357-a9ee288728e0?q=80&w=1000',
                'Milano' => 'https://images.unsplash.com/photo-1515674447568-09bbb507b96c?q=80&w=1000',
                'Napoli' => 'https://images.unsplash.com/photo-1534308983496-4fabb1a015ee?q=80&w=1000',
                'Pisa' => 'https://images.unsplash.com/photo-1516186366443-0744a82bac62?q=80&w=1000',
                'Torino' => 'https://images.unsplash.com/photo-1486939901431-4ddf3c209975?q=80&w=1000',
                'Bologna' => 'https://images.unsplash.com/photo-1559036545-7af4ab23be13?q=80&w=1000',
                'Genova' => 'https://www.portsofgenoa.com/images/magazine/NOTIZIE_Imm/Genova_ph_merlofotografia_220228-0421_RID.webp',
                'Verona' => 'https://images.unsplash.com/photo-1588693273928-92fa26159c88?q=80&w=1000',
                'Siena' => 'https://images.unsplash.com/photo-1560439747-08df04b1bff4?q=80&w=1000',
                'Palermo' => 'https://images.unsplash.com/photo-1547636238-5666fb35bee7?q=80&w=1000'

            ];

            // Immagine di fallback per città non presenti nell'array
            $fallbackImage = 'https://images.unsplash.com/photo-1516483638261-f4dbaf036963?q=80&w=1000';

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
                        <p class="testimonial-name">Laura B.</p>
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
                        <p class="testimonial-name">Marco T.</p>
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
                        <p class="testimonial-name">Giovanni R.</p>
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

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <h5 class="footer-heading">Artifex</h5>
                <p>Specialisti in visite guidate di alto livello per i siti culturali e storici più importanti d'Italia. La nostra missione è rendere accessibile e memorabile il patrimonio artistico italiano.</p>
                <div class="mt-4">
                    <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h5 class="footer-heading">Esplora</h5>
                <a href="index.php" class="footer-link">Home</a>
                <a href="visite.php" class="footer-link">Visite</a>
                <a href="guide.php" class="footer-link">Guide</a>
                <a href="contatti.php" class="footer-link">Contatti</a>
                <a href="chi-siamo.php" class="footer-link">Chi Siamo</a>
            </div>
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h5 class="footer-heading">Utili</h5>
                <a href="faq.php" class="footer-link">FAQ</a>
                <a href="termini.php" class="footer-link">Termini e Condizioni</a>
                <a href="privacy.php" class="footer-link">Privacy Policy</a>
                <a href="cookie.php" class="footer-link">Cookie Policy</a>
            </div>
            <div class="col-lg-4 col-md-4">
                <h5 class="footer-heading">Contatti</h5>
                <p><i class="fas fa-map-marker-alt me-2"></i> Via Alcide de Gasperi, 45100 Rovigo, Italia</p>
                <p><i class="fas fa-phone me-2"></i> +39 06 1234567</p>
                <p><i class="fas fa-envelope me-2"></i> info@artifex.it</p>
            </div>
        </div>
        <hr class="mt-4 mb-4" style="border-color: rgba(255, 255, 255, 0.1);">
        <div class="row">
            <div class="col-md-12 text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Artifex by Xiaqiang Qiu 5F ITIS VIOLA MARCHESINI</p>
            </div>
        </div>
    </div>
</footer>

<!-- jQuery required by Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide alert messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Add animation for cards on hover
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.classList.add('card-hover');
            });
            card.addEventListener('mouseleave', function() {
                this.classList.remove('card-hover');
            });
        });
    });
</script>
</body>
</html>