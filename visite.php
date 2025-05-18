<?php
// Connessione al database
require_once 'DB_CONNECT/db_config.php';
require_once 'DB_CONNECT/functions.php';
require_once 'DB_CONNECT/database_Connect.php';
$config = require 'DB_CONNECT/db_config.php';
$db = DataBase_Connect::getDB($config);

// Controlla se l'utente è loggato
session_start();
$logged_in = isset($_SESSION['user_id']);
$is_admin = $logged_in && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'amministratore';

// Lista delle 5 visite specifiche da mostrare (rimosso "Splendori della Serenissima")
$visite_da_mostrare = [
    'Leggende del Castello sul Mare',
    'Percorso sulla Terrazza del Duomo',
    'Storia di Firenze e dei Medici',
    'Tour Completo Roma Antica',
    'Venezia e le sue Isole'
];

// Query per ottenere solo le 5 visite specificate
$sql = "SELECT DISTINCT v.vid, v.titolo, v.descrizione, v.durata_media, 
        MIN(e.prezzo) as prezzo_min, 
        si.luogo, 
        GROUP_CONCAT(DISTINCT si.nome SEPARATOR ', ') as siti_names,
        (SELECT MIN(ev.inizio) FROM eventi ev WHERE ev.id_visita = v.vid AND ev.inizio > NOW()) as prossimo_evento
        FROM visite v
        JOIN visite_siti vs ON v.vid = vs.id_visita
        JOIN siti_interesse si ON vs.id_sito = si.sid
        JOIN eventi e ON v.vid = e.id_visita
        WHERE e.inizio > NOW() AND v.titolo IN (?, ?, ?, ?, ?)
        GROUP BY v.vid, v.titolo, v.descrizione, v.durata_media, si.luogo
        ORDER BY v.titolo ASC";

// Esegui la query
$stmt = $db->prepare($sql);
$stmt->execute($visite_da_mostrare);
$visite = $stmt->fetchAll(PDO::FETCH_OBJ);

// Funzione per formattare la durata
function formatta_durata($minuti) {
    $ore = floor($minuti / 60);
    $min = $minuti % 60;

    if ($ore > 0) {
        return $ore . 'h ' . ($min > 0 ? $min . 'min' : '');
    } else {
        return $min . ' min';
    }
}

// Utilizzo le immagini che hai già fornito
$tourImages = [
    'Storia di Firenze e dei Medici' => 'https://www.hotelbrunelleschi.it/wp-content/uploads/weekend-a-firenze-800x534.jpg',
    'Venezia e le sue Isole' => 'https://www.italia.it/content/dam/tdh/it/site1/getty-temp-image/category-nuove/venezia/1600X900_venezia_san_marco_gabbiano.jpg',
    'Percorso sulla Terrazza del Duomo' => 'https://www.duomomilano.it/wp-content/uploads/2024/01/terrazze-duomo-di-milano.jpg',
    'Tour Completo Roma Antica' => 'https://tourismmedia.italia.it/is/image/mitur/20220127150143-colosseo-roma-lazio-shutterstock-756032350?wid=1600&hei=900&fit=constrain,1&fmt=webp',
    'Leggende del Castello sul Mare' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTjP1GIEVa1-zyhciuOFNQGCU-tvAj_U9pS0g&s'
];

// Immagine di fallback
$fallbackImage = 'https://www.lafinestraaccanto.com/wp-content/uploads/2024/09/Alberobello-in-evidenza.jpg';
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

<section class="py-5">
    <div class="container">
        <h1 class="text-center mb-5">Le Nostre Visite Guidate</h1>
        <p class="lead text-center mb-5">Esplora il patrimonio culturale italiano con guide esperte</p>

        <!-- Lista Visite -->
        <div class="row">
            <?php if (count($visite) > 0): ?>
                <?php foreach ($visite as $visita):
                    // Determina l'immagine da usare
                    $imgSrc = isset($tourImages[$visita->titolo]) ? $tourImages[$visita->titolo] : $fallbackImage;

                    // Formatta la data del prossimo evento
                    $data_evento = new DateTime($visita->prossimo_evento);
                    $data_formattata = $data_evento->format('d/m/Y H:i');
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card tour-card">
                            <img src="<?php echo $imgSrc; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($visita->titolo); ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="badge bg-info text-dark badge-custom">
                                        <i class="far fa-clock me-1"></i> <?php echo formatta_durata($visita->durata_media); ?>
                                    </span>
                                    <span class="badge bg-success badge-custom">
                                        <i class="fas fa-euro-sign me-1"></i> Da <?php echo number_format($visita->prezzo_min, 2); ?>€
                                    </span>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($visita->titolo); ?></h5>
                                <p class="card-text small text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($visita->luogo); ?> -
                                    <?php echo htmlspecialchars($visita->siti_names); ?>
                                </p>
                                <p class="card-text">
                                    <?php echo substr(htmlspecialchars($visita->descrizione), 0, 100) . '...'; ?>
                                </p>
                                <p class="small mb-3">
                                    <i class="far fa-calendar-alt me-1"></i> Prossima data: <?php echo $data_formattata; ?>
                                </p>
                                <a href="dettaglio_visita.php?id=<?php echo $visita->vid; ?>" class="btn btn-primary w-100">Dettagli e Prenotazione</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading">Nessuna visita trovata!</h4>
                    <p>Al momento non ci sono visite disponibili. Riprova più tardi.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'PageComponenti/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>