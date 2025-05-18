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

// Ottieni l'ID della visita dalla URL
if (!isset($_GET['id'])) {
    header('Location: visite.php');
    exit;
}

$visita_id = (int)$_GET['id'];

// Verifica che sia una delle visite che vogliamo mostrare (3, 4, 5, 11, 13)
$visite_permesse = [3, 4, 5, 11, 13];
if (!in_array($visita_id, $visite_permesse)) {
    header('Location: visite.php');
    exit;
}

// Query per ottenere i dettagli della visita
$sql = "SELECT v.vid, v.titolo, v.descrizione, v.durata_media, 
        GROUP_CONCAT(DISTINCT si.nome SEPARATOR ', ') as siti_names,
        GROUP_CONCAT(DISTINCT si.sid) as siti_ids,
        GROUP_CONCAT(DISTINCT si.luogo SEPARATOR ', ') as luoghi
        FROM visite v
        JOIN visite_siti vs ON v.vid = vs.id_visita
        JOIN siti_interesse si ON vs.id_sito = si.sid
        WHERE v.vid = ?
        GROUP BY v.vid, v.titolo, v.descrizione, v.durata_media";

$stmt = $db->prepare($sql);
$stmt->execute([$visita_id]);
$visita = $stmt->fetch(PDO::FETCH_OBJ);

// Se la visita non esiste, redirect
if (!$visita) {
    header('Location: visite.php');
    exit;
}

// Query per ottenere gli eventi associati alla visita (solo quelli futuri)
$sql_eventi = "SELECT e.eid, e.inizio, e.minimo_partecipanti, e.massimo_partecipanti, 
              e.prezzo, g.nome as guida_nome, g.cognome as guida_cognome, 
              l.lingua,
              (SELECT COUNT(*) FROM ordini o WHERE o.id_evento = e.eid) as posti_prenotati
              FROM eventi e
              JOIN illustrazioni i ON e.eid = i.id_evento
              JOIN guide g ON i.id_guida = g.gid
              JOIN lingue l ON i.id_lingua = l.lid
              WHERE e.id_visita = ? AND e.inizio > NOW()
              ORDER BY e.inizio ASC";

$stmt_eventi = $db->prepare($sql_eventi);
$stmt_eventi->execute([$visita_id]);
$eventi = $stmt_eventi->fetchAll(PDO::FETCH_OBJ);

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

// Mappa delle immagini per le visite specifiche
$tourImages = [
    3 => 'https://www.duomomilano.it/wp-content/uploads/2024/01/terrazze-duomo-di-milano.jpg', // Percorso sulla Terrazza del Duomo
    4 => 'https://www.hotelbrunelleschi.it/wp-content/uploads/weekend-a-firenze-800x534.jpg', // Storia di Firenze e dei Medici
    5 => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTjP1GIEVa1-zyhciuOFNQGCU-tvAj_U9pS0g&s', // Leggende del Castello sul Mare
    11 => 'https://tourismmedia.italia.it/is/image/mitur/20220127150143-colosseo-roma-lazio-shutterstock-756032350?wid=1600&hei=900&fit=constrain,1&fmt=webp', // Tour Completo Roma Antica
    13 => 'https://www.italia.it/content/dam/tdh/it/site1/getty-temp-image/category-nuove/venezia/1600X900_venezia_san_marco_gabbiano.jpg' // Venezia e le sue Isole
];

// Immagine di fallback
$fallbackImage = 'https://www.lafinestraaccanto.com/wp-content/uploads/2024/09/Alberobello-in-evidenza.jpg';

// Determina l'immagine da usare
$imgSrc = isset($tourImages[$visita_id]) ? $tourImages[$visita_id] : $fallbackImage;

// Gestione dell'aggiunta al carrello
$messaggio = '';
$tipo_messaggio = '';

if (isset($_POST['aggiungi_carrello']) && $logged_in) {
    $evento_id = (int)$_POST['evento_id'];
    $quantita = (int)$_POST['quantita'];
    $user_id = $_SESSION['user_id'];

    // Verifica se l'evento esiste e ha posti disponibili
    $sql_check = "SELECT e.eid, e.massimo_partecipanti, 
                 (SELECT COUNT(*) FROM ordini o WHERE o.id_evento = e.eid) as posti_prenotati
                 FROM eventi e WHERE e.eid = ?";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute([$evento_id]);
    $evento = $stmt_check->fetch(PDO::FETCH_OBJ);

    if ($evento && ($evento->posti_prenotati + $quantita) <= $evento->massimo_partecipanti) {
        // Controlla se l'evento è già nel carrello
        $sql_carrello_check = "SELECT cid, quantita FROM carrello WHERE id_evento = ? AND id_turista = ?";
        $stmt_carrello_check = $db->prepare($sql_carrello_check);
        $stmt_carrello_check->execute([$evento_id, $user_id]);
        $carrello_esistente = $stmt_carrello_check->fetch(PDO::FETCH_OBJ);

        if ($carrello_esistente) {
            // Aggiorna la quantità
            $nuova_quantita = $carrello_esistente->quantita + $quantita;
            $sql_update = "UPDATE carrello SET quantita = ? WHERE cid = ?";
            $stmt_update = $db->prepare($sql_update);
            $risultato = $stmt_update->execute([$nuova_quantita, $carrello_esistente->cid]);
        } else {
            // Inserisci nuovo record nel carrello
            $sql_insert = "INSERT INTO carrello (id_evento, id_turista, quantita) VALUES (?, ?, ?)";
            $stmt_insert = $db->prepare($sql_insert);
            $risultato = $stmt_insert->execute([$evento_id, $user_id, $quantita]);
        }

        if (isset($risultato) && $risultato) {
            $messaggio = "Evento aggiunto al carrello con successo!";
            $tipo_messaggio = "success";
        } else {
            $messaggio = "Errore durante l'aggiunta al carrello.";
            $tipo_messaggio = "danger";
        }
    } else {
        $messaggio = "Non ci sono abbastanza posti disponibili per questo evento.";
        $tipo_messaggio = "warning";
    }
}

// Gestione della prenotazione diretta
if (isset($_POST['prenota_ora']) && $logged_in) {
    $evento_id = (int)$_POST['evento_id'];
    $quantita = (int)$_POST['quantita'];
    $user_id = $_SESSION['user_id'];

    // Verifica se l'evento esiste e ha posti disponibili
    $sql_check = "SELECT e.eid, e.massimo_partecipanti, 
                 (SELECT COUNT(*) FROM ordini o WHERE o.id_evento = e.eid) as posti_prenotati
                 FROM eventi e WHERE e.eid = ?";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute([$evento_id]);
    $evento = $stmt_check->fetch(PDO::FETCH_OBJ);

    if ($evento && ($evento->posti_prenotati + $quantita) <= $evento->massimo_partecipanti) {
        // Inserisci direttamente nell'ordine
        $sql_insert = "INSERT INTO ordini (id_evento, id_turista, data, quantita) VALUES (?, ?, NOW(), ?)";
        $stmt_insert = $db->prepare($sql_insert);
        $risultato = $stmt_insert->execute([$evento_id, $user_id, $quantita]);

        if ($risultato) {
            $messaggio = "Prenotazione effettuata con successo!";
            $tipo_messaggio = "success";
        } else {
            $messaggio = "Errore durante la prenotazione.";
            $tipo_messaggio = "danger";
        }
    } else {
        $messaggio = "Non ci sono abbastanza posti disponibili per questo evento.";
        $tipo_messaggio = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artifex - <?php echo htmlspecialchars($visita->titolo); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/index.css" rel="stylesheet">
    <link href="CSS/dettaglio.css" rel="stylesheet">
</head>
<body>
<?php include 'PageComponenti/header.php'; ?>

<div class="container my-5">
    <?php if (!empty($messaggio)): ?>
        <div class="alert alert-<?php echo $tipo_messaggio; ?> alert-dismissible fade show" role="alert">
            <?php echo $messaggio; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Colonna principale con i dettagli della visita -->
        <div class="col-lg-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="visite.php">Visite Guidate</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($visita->titolo); ?></li>
                </ol>
            </nav>

            <h1 class="mb-4"><?php echo htmlspecialchars($visita->titolo); ?></h1>

            <div class="d-flex align-items-center mb-4">
                <span class="badge bg-info text-dark me-2 badge-custom">
                    <i class="far fa-clock me-1"></i> <?php echo formatta_durata($visita->durata_media); ?>
                </span>
                <span class="badge bg-primary me-2 badge-custom">
                    <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($visita->luoghi); ?>
                </span>
                <?php if (!empty($eventi)): ?>
                    <span class="badge bg-success badge-custom">
                    <i class="fas fa-euro-sign me-1"></i> Da <?php echo number_format($eventi[0]->prezzo, 2); ?>€
                </span>
                <?php endif; ?>
            </div>

            <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($visita->titolo); ?>" class="img-fluid mb-4 tour-image w-100">

            <div class="row mb-5">
                <div class="col-md-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h5>Luoghi di interesse</h5>
                    <p><?php echo htmlspecialchars($visita->siti_names); ?></p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Durata</h5>
                    <p><?php echo formatta_durata($visita->durata_media); ?></p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h5>Gruppi</h5>
                    <p>Max <?php echo !empty($eventi) ? $eventi[0]->massimo_partecipanti : '0'; ?> persone</p>
                </div>
            </div>

            <h2 class="mb-3">Descrizione</h2>
            <p class="lead mb-5"><?php echo htmlspecialchars($visita->descrizione); ?></p>

            <h2 class="mb-4">Date disponibili</h2>

            <?php if (count($eventi) > 0): ?>
                <?php foreach ($eventi as $evento):
                    $data_evento = new DateTime($evento->inizio);
                    $data_formattata = $data_evento->format('d/m/Y H:i');
                    $posti_disponibili = $evento->massimo_partecipanti - $evento->posti_prenotati;
                    ?>
                    <div class="card evento-card mb-3">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h5 class="mb-0"><?php echo $data_formattata; ?></h5>
                                    <small class="text-muted">
                                        <i class="fas fa-language me-1"></i> <?php echo htmlspecialchars($evento->lingua); ?>
                                    </small>
                                </div>
                                <div class="col-md-3">
                                    <p class="mb-0">
                                        <strong>Guida:</strong> <?php echo htmlspecialchars($evento->guida_nome . ' ' . $evento->guida_cognome); ?>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                <span class="badge bg-<?php echo $posti_disponibili > 5 ? 'success' : ($posti_disponibili > 0 ? 'warning' : 'danger'); ?>">
                                    <?php echo $posti_disponibili; ?> posti disponibili
                                </span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <strong class="d-block mb-2"><?php echo number_format($evento->prezzo, 2); ?>€</strong>
                                    <button class="btn btn-sm btn-outline-primary seleziona-evento"
                                            data-evento-id="<?php echo $evento->eid; ?>"
                                            data-evento-data="<?php echo $data_formattata; ?>"
                                            data-evento-prezzo="<?php echo number_format($evento->prezzo, 2); ?>"
                                            data-evento-lingua="<?php echo htmlspecialchars($evento->lingua); ?>"
                                            data-evento-posti="<?php echo $posti_disponibili; ?>">
                                        Seleziona
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    Non ci sono eventi disponibili per questa visita al momento.
                </div>
            <?php endif; ?>

            <div class="testimonial">
                <p>"Un'esperienza unica ed emozionante! La guida era molto preparata e ci ha fatto scoprire aspetti di questa meraviglia che non conoscevo. Da non perdere assolutamente."</p>
                <small class="d-block text-end">— Un visitatore soddisfatto</small>
            </div>
        </div>

        <!-- Colonna laterale con form di prenotazione -->
        <div class="col-lg-4">
            <div class="card prenotazione-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Prenota questa visita</h5>
                </div>
                <div class="card-body">
                    <?php if ($logged_in): ?>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="evento-select" class="form-label">Seleziona una data:</label>
                                <select class="form-select" id="evento-select" name="evento_id" required>
                                    <option value="">Seleziona una data</option>
                                    <?php foreach ($eventi as $evento):
                                        $data_evento = new DateTime($evento->inizio);
                                        $data_formattata = $data_evento->format('d/m/Y H:i');
                                        $posti_disponibili = $evento->massimo_partecipanti - $evento->posti_prenotati;

                                        // Disabilita l'opzione se non ci sono posti disponibili
                                        $disabled = $posti_disponibili <= 0 ? 'disabled' : '';
                                        ?>
                                        <option value="<?php echo $evento->eid; ?>" <?php echo $disabled; ?>
                                                data-prezzo="<?php echo number_format($evento->prezzo, 2); ?>"
                                                data-posti="<?php echo $posti_disponibili; ?>"
                                                data-lingua="<?php echo htmlspecialchars($evento->lingua); ?>">
                                            <?php echo $data_formattata; ?> - <?php echo htmlspecialchars($evento->lingua); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="dettagli-evento" class="d-none mb-3">
                                <div class="alert alert-light border">
                                    <p class="mb-1"><strong>Data:</strong> <span id="data-display"></span></p>
                                    <p class="mb-1"><strong>Lingua:</strong> <span id="lingua-display"></span></p>
                                    <p class="mb-1"><strong>Prezzo:</strong> <span id="prezzo-display"></span>€</p>
                                    <p class="mb-0"><strong>Posti disponibili:</strong> <span id="posti-display"></span></p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="quantita" class="form-label">Numero di partecipanti:</label>
                                <input type="number" class="form-control" id="quantita" name="quantita" min="1" value="1" required>
                            </div>

                            <div class="mb-2">
                                <p class="mb-1">Prezzo totale:</p>
                                <h4 id="prezzo-totale">0.00€</h4>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="prenota_ora" class="btn btn-primary">
                                    <i class="fas fa-calendar-check me-2"></i>Prenota ora
                                </button>
                                <button type="submit" name="aggiungi_carrello" class="btn btn-outline-primary">
                                    <i class="fas fa-shopping-cart me-2"></i>Aggiungi al carrello
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-user-lock fa-3x mb-3 text-secondary"></i>
                            <h5>Accedi per prenotare</h5>
                            <p class="mb-3">Devi essere registrato ed aver effettuato l'accesso per prenotare questa visita</p>
                            <div class="d-grid gap-2">
                                <a href="login.php" class="btn btn-primary">Accedi</a>
                                <a href="registrazione.php" class="btn btn-outline-secondary">Registrati</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <small>
                        <i class="fas fa-info-circle me-1"></i> I posti sono limitati, prenota in anticipo per garantirti la disponibilità.
                    </small>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Hai domande?</h5>
                    <p class="card-text">Contattaci per qualsiasi informazione aggiuntiva sulla visita o sulla prenotazione.</p>
                    <a href="contatti.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-envelope me-2"></i>Contattaci
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'PageComponenti/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="Function/dettaglio.js"></script>
</body>
</html>