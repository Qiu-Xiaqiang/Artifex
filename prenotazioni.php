<?php
// Connessione al database
require_once 'DB_CONNECT/db_config.php';
require_once 'DB_CONNECT/functions.php';
require_once 'DB_CONNECT/database_Connect.php';
$config = require 'DB_CONNECT/db_config.php';
$db = DataBase_Connect::getDB($config);

// Controlla se l'utente è loggato
session_start();
if (!isset($_SESSION['user_id'])) {
    // Reindirizza alla pagina di login se l'utente non è loggato
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$messaggio = '';
$tipo_messaggio = '';

// Verifica se è stata richiesta una cancellazione
if (isset($_POST['cancella_prenotazione']) && isset($_POST['ordine_id'])) {
    $ordine_id = (int)$_POST['ordine_id'];

    // Verifica che l'ordine appartenga all'utente corrente
    $sql_check = "SELECT oid FROM ordini WHERE oid = ? AND id_turista = ?";
    $stmt_check = $db->prepare($sql_check);
    $stmt_check->execute([$ordine_id, $user_id]);

    if ($stmt_check->rowCount() > 0) {
        // Controllo che l'evento non sia già passato
        $sql_evento_check = "SELECT e.inizio 
                            FROM ordini o 
                            JOIN eventi e ON o.id_evento = e.eid 
                            WHERE o.oid = ? AND e.inizio > NOW()";
        $stmt_evento_check = $db->prepare($sql_evento_check);
        $stmt_evento_check->execute([$ordine_id]);

        if ($stmt_evento_check->rowCount() > 0) {
            // Cancella l'ordine
            $sql_delete = "DELETE FROM ordini WHERE oid = ?";
            $stmt_delete = $db->prepare($sql_delete);

            if ($stmt_delete->execute([$ordine_id])) {
                $messaggio = "Prenotazione cancellata con successo.";
                $tipo_messaggio = "success";
            } else {
                $messaggio = "Errore durante la cancellazione della prenotazione.";
                $tipo_messaggio = "danger";
            }
        } else {
            $messaggio = "Non è possibile cancellare prenotazioni per eventi già passati.";
            $tipo_messaggio = "warning";
        }
    } else {
        $messaggio = "Errore: prenotazione non trovata o non autorizzata.";
        $tipo_messaggio = "danger";
    }
}

// Query per ottenere tutte le prenotazioni dell'utente
$sql = "SELECT o.oid, o.data as data_ordine, o.quantita, 
        e.eid, e.inizio as data_evento, e.prezzo, 
        v.vid, v.titolo as visita_titolo, v.durata_media,
        g.nome as guida_nome, g.cognome as guida_cognome,
        l.lingua,
        GROUP_CONCAT(DISTINCT si.nome SEPARATOR ', ') as siti_names,
        GROUP_CONCAT(DISTINCT si.luogo SEPARATOR ', ') as luoghi
        FROM ordini o
        JOIN eventi e ON o.id_evento = e.eid
        JOIN visite v ON e.id_visita = v.vid
        JOIN illustrazioni i ON e.eid = i.id_evento
        JOIN guide g ON i.id_guida = g.gid
        JOIN lingue l ON i.id_lingua = l.lid
        JOIN visite_siti vs ON v.vid = vs.id_visita
        JOIN siti_interesse si ON vs.id_sito = si.sid
        WHERE o.id_turista = ?
        GROUP BY o.oid, o.data, o.quantita, e.eid, e.inizio, e.prezzo, v.vid, v.titolo, v.durata_media, g.nome, g.cognome, l.lingua
        ORDER BY e.inizio DESC";

$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$prenotazioni = $stmt->fetchAll(PDO::FETCH_OBJ);

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

// Funzione per ottenere una classe CSS in base alla data dell'evento (passato, imminente, futuro)
function getStatusClass($data_evento) {
    $now = new DateTime();
    $evento_date = new DateTime($data_evento);
    $diff = $now->diff($evento_date);

    if ($evento_date < $now) {
        return "past"; // Evento passato
    } elseif ($diff->days <= 3) {
        return "imminent"; // Evento imminente (nei prossimi 3 giorni)
    } else {
        return "future"; // Evento futuro
    }
}

// Funzione per ottenere un messaggio in base allo stato dell'evento
function getStatusMessage($data_evento) {
    $now = new DateTime();
    $evento_date = new DateTime($data_evento);
    $diff = $now->diff($evento_date);

    if ($evento_date < $now) {
        return "Evento completato";
    } elseif ($diff->days == 0) {
        if ($diff->h == 0) {
            return "Tra " . $diff->i . " minuti";
        }
        return "Oggi, tra " . $diff->h . " ore";
    } elseif ($diff->days == 1) {
        return "Domani";
    } elseif ($diff->days <= 3) {
        return "Tra " . $diff->days . " giorni";
    } else {
        return "Confermato";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artifex - Le mie prenotazioni</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/index.css" rel="stylesheet">
    <link href="CSS/prenotazioni.css" rel="stylesheet">
</head>
<body>
<?php include 'PageComponenti/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <h1 class="text-center mb-2">Le mie prenotazioni</h1>
            <p class="text-center text-muted mb-5">Gestisci tutte le tue visite prenotate</p>

            <?php if (!empty($messaggio)): ?>
                <div class="alert alert-<?php echo $tipo_messaggio; ?> alert-dismissible fade show" role="alert">
                    <?php echo $messaggio; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="filter-section">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h5 class="mb-3 mb-md-0">Filtri</h5>
                    </div>
                    <div class="col-md-5">
                        <select id="status-filter" class="form-select">
                            <option value="all">Tutti gli eventi</option>
                            <option value="future">Solo eventi futuri</option>
                            <option value="past">Solo eventi passati</option>
                        </select>
                    </div>
                </div>
            </div>

            <?php if (count($prenotazioni) > 0): ?>
                <div class="prenotazioni-list">
                    <?php foreach ($prenotazioni as $prenotazione):
                        $data_ordine = new DateTime($prenotazione->data_ordine);
                        $data_evento = new DateTime($prenotazione->data_evento);
                        $statusClass = getStatusClass($prenotazione->data_evento);
                        $statusMessage = getStatusMessage($prenotazione->data_evento);
                        $importo_totale = $prenotazione->prezzo * $prenotazione->quantita;
                        ?>
                        <div class="card prenotazione-card <?php echo $statusClass; ?>">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h4><?php echo htmlspecialchars($prenotazione->visita_titolo); ?></h4>
                                        <div class="prenotazione-info">
                                            <span class="badge bg-<?php echo $statusClass == 'past' ? 'secondary' : 'primary'; ?> me-2">
                                                <i class="far fa-calendar-alt me-1"></i> <?php echo $data_evento->format('d/m/Y H:i'); ?>
                                            </span>
                                            <span class="badge bg-info text-dark me-2">
                                                <i class="far fa-clock me-1"></i> <?php echo formatta_durata($prenotazione->durata_media); ?>
                                            </span>
                                            <span class="badge bg-success">
                                                <i class="fas fa-language me-1"></i> <?php echo htmlspecialchars($prenotazione->lingua); ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-map-marker-alt me-1 text-secondary"></i> <?php echo htmlspecialchars($prenotazione->luoghi); ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-user me-1 text-secondary"></i> Guida: <strong><?php echo htmlspecialchars($prenotazione->guida_nome . ' ' . $prenotazione->guida_cognome); ?></strong>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-ticket-alt me-1 text-secondary"></i> <?php echo $prenotazione->quantita; ?> <?php echo $prenotazione->quantita > 1 ? 'partecipanti' : 'partecipante'; ?>
                                        </p>
                                        <p class="mb-0 text-muted small">
                                            <i class="fas fa-shopping-cart me-1"></i> Prenotato il <?php echo $data_ordine->format('d/m/Y'); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <div class="mb-3">
                                            <span class="badge badge-status badge-<?php echo $statusClass; ?>">
                                                <?php echo $statusMessage; ?>
                                            </span>
                                        </div>
                                        <div class="evento-prezzo mb-3">
                                            <?php echo number_format($importo_totale, 2); ?>€
                                        </div>

                                        <div class="prenotazione-actions">
                                            <?php if ($statusClass != 'past'): ?>
                                                <form method="post" action="" onsubmit="return confirm('Sei sicuro di voler cancellare questa prenotazione?');">
                                                    <input type="hidden" name="ordine_id" value="<?php echo $prenotazione->oid; ?>">
                                                    <div class="d-grid gap-2 d-md-block">
                                                        <a href="dettaglio_visite.php?id=<?php echo $prenotazione->vid; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-info-circle me-1"></i> Dettagli
                                                        </a>
                                                        <button type="submit" name="cancella_prenotazione" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-times me-1"></i> Cancella
                                                        </button>
                                                    </div>
                                                </form>
                                            <?php else: ?>
                                                <a href="dettaglio_visite.php?id=<?php echo $prenotazione->vid; ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-info-circle me-1"></i> Dettagli
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-xmark"></i>
                    <h3>Nessuna prenotazione trovata</h3>
                    <p class="text-muted mb-4">Non hai ancora prenotato nessuna visita guidata con noi.</p>
                    <a href="visite.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Esplora le visite disponibili
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'PageComponenti/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="Function/prenotazioni.js"></script>
</body>
</html>