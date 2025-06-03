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
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$messaggio = '';
$tipo_messaggio = '';

// Inizializza il carrello se non esiste
if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

// Gestione aggiunta al carrello
if (isset($_POST['aggiungi_carrello'])) {
    $evento_id = (int)$_POST['evento_id'];
    $quantita = (int)$_POST['quantita'];

    if ($quantita > 0) {
        $_SESSION['carrello'][$evento_id] = $quantita;
        $messaggio = "Evento aggiunto al carrello con successo.";
        $tipo_messaggio = "success";
    }
}

// Gestione rimozione dal carrello
if (isset($_POST['rimuovi_carrello'])) {
    $evento_id = (int)$_POST['evento_id'];
    unset($_SESSION['carrello'][$evento_id]);
    $messaggio = "Evento rimosso dal carrello.";
    $tipo_messaggio = "info";
}

// Gestione modifica quantità
if (isset($_POST['modifica_quantita'])) {
    $evento_id = (int)$_POST['evento_id'];
    $nuova_quantita = (int)$_POST['nuova_quantita'];

    if ($nuova_quantita > 0) {
        $_SESSION['carrello'][$evento_id] = $nuova_quantita;
        $messaggio = "Quantità aggiornata con successo.";
        $tipo_messaggio = "success";
    } else {
        unset($_SESSION['carrello'][$evento_id]);
        $messaggio = "Evento rimosso dal carrello.";
        $tipo_messaggio = "info";
    }
}

// Gestione checkout
if (isset($_POST['checkout'])) {
    $nome_carta = trim($_POST['nome_carta']);
    $numero_carta = trim($_POST['numero_carta']);
    $scadenza = trim($_POST['scadenza']);
    $cvv = trim($_POST['cvv']);
    $indirizzo_fatturazione = trim($_POST['indirizzo_fatturazione']);

    // Validazione base dei dati
    if (empty($nome_carta) || empty($numero_carta) || empty($scadenza) || empty($cvv) || empty($indirizzo_fatturazione)) {
        $messaggio = "Tutti i campi sono obbligatori.";
        $tipo_messaggio = "danger";
    } elseif (!preg_match('/^\d{16}$/', str_replace(' ', '', $numero_carta))) {
        $messaggio = "Numero carta non valido.";
        $tipo_messaggio = "danger";
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $messaggio = "CVV non valido.";
        $tipo_messaggio = "danger";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $scadenza)) {
        $messaggio = "Data di scadenza non valida (formato MM/YY).";
        $tipo_messaggio = "danger";
    } else {
        // Processa tutti gli ordini del carrello
        $db->beginTransaction();

        try {
            foreach ($_SESSION['carrello'] as $evento_id => $quantita) {
                $sql_insert = "INSERT INTO ordini (id_turista, id_evento, quantita, data) VALUES (?, ?, ?, NOW())";
                $stmt_insert = $db->prepare($sql_insert);
                $stmt_insert->execute([$user_id, $evento_id, $quantita]);
            }

            $db->commit();

            // Svuota il carrello
            $_SESSION['carrello'] = [];

            $messaggio = "Pagamento completato con successo! Le tue prenotazioni sono state confermate.";
            $tipo_messaggio = "success";

            // Reindirizza alle prenotazioni dopo 3 secondi
            header("refresh:3;url=prenotazioni.php");

        } catch (Exception $e) {
            $db->rollback();
            $messaggio = "Errore durante l'elaborazione del pagamento. Riprova.";
            $tipo_messaggio = "danger";
        }
    }
}

// Ottieni dettagli degli eventi nel carrello
$eventi_carrello = [];
$totale_carrello = 0;

if (!empty($_SESSION['carrello'])) {
    $eventi_ids = array_keys($_SESSION['carrello']);
    $placeholders = str_repeat('?,', count($eventi_ids) - 1) . '?';

    $sql = "SELECT e.eid, e.inizio as data_evento, e.prezzo, 
            v.vid, v.titolo as visita_titolo, v.durata_media,
            g.nome as guida_nome, g.cognome as guida_cognome,
            l.lingua,
            GROUP_CONCAT(DISTINCT si.nome SEPARATOR ', ') as siti_names,
            GROUP_CONCAT(DISTINCT si.luogo SEPARATOR ', ') as luoghi
            FROM eventi e
            JOIN visite v ON e.id_visita = v.vid
            JOIN illustrazioni i ON e.eid = i.id_evento
            JOIN guide g ON i.id_guida = g.gid
            JOIN lingue l ON i.id_lingua = l.lid
            JOIN visite_siti vs ON v.vid = vs.id_visita
            JOIN siti_interesse si ON vs.id_sito = si.sid
            WHERE e.eid IN ($placeholders) AND e.inizio > NOW()
            GROUP BY e.eid, e.inizio, e.prezzo, v.vid, v.titolo, v.durata_media, g.nome, g.cognome, l.lingua
            ORDER BY e.inizio ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($eventi_ids);
    $eventi_carrello = $stmt->fetchAll(PDO::FETCH_OBJ);

    // Calcola il totale
    foreach ($eventi_carrello as $evento) {
        $quantita = $_SESSION['carrello'][$evento->eid];
        $totale_carrello += $evento->prezzo * $quantita;
    }
}

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
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artifex - Carrello</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/index.css" rel="stylesheet">
    <link href="CSS/carrello.css" rel="stylesheet">
</head>
<body>
<?php include 'PageComponenti/header.php'; ?>

<div class="container my-5">
    <div class="carrello-container">
        <h1 class="text-center mb-2">Il tuo carrello</h1>
        <p class="text-center text-muted mb-5">Rivedi le tue selezioni e completa la prenotazione</p>

        <?php if (!empty($messaggio)): ?>
            <div class="alert alert-<?php echo $tipo_messaggio; ?> alert-dismissible fade show" role="alert">
                <?php echo $messaggio; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($eventi_carrello)): ?>
            <div class="row">
                <div class="col-lg-8">
                    <h4 class="mb-4">Eventi selezionati</h4>

                    <?php foreach ($eventi_carrello as $evento):
                        $quantita = $_SESSION['carrello'][$evento->eid];
                        $data_evento = new DateTime($evento->data_evento);
                        $subtotale = $evento->prezzo * $quantita;
                        ?>
                        <div class="evento-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="evento-info">
                                        <h5><?php echo htmlspecialchars($evento->visita_titolo); ?></h5>
                                        <div class="mb-2">
                                            <span class="badge badge-custom me-2">
                                                <i class="far fa-calendar-alt me-1"></i> <?php echo $data_evento->format('d/m/Y H:i'); ?>
                                            </span>
                                            <span class="badge badge-custom me-2">
                                                <i class="far fa-clock me-1"></i> <?php echo formatta_durata($evento->durata_media); ?>
                                            </span>
                                            <span class="badge badge-custom">
                                                <i class="fas fa-language me-1"></i> <?php echo htmlspecialchars($evento->lingua); ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-1">
                                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($evento->luoghi); ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="fas fa-user me-1"></i> Guida: <strong><?php echo htmlspecialchars($evento->guida_nome . ' ' . $evento->guida_cognome); ?></strong>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="prezzo-evento mb-2">
                                        <?php echo number_format($evento->prezzo, 2); ?>€ <small class="text-muted">per persona</small>
                                    </div>

                                    <form method="post" action="" class="d-inline">
                                        <input type="hidden" name="evento_id" value="<?php echo $evento->eid; ?>">
                                        <div class="quantita-controls">
                                            <button type="button" class="quantita-btn" onclick="modificaQuantita(<?php echo $evento->eid; ?>, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" name="nuova_quantita" value="<?php echo $quantita; ?>"
                                                   min="1" max="10" class="quantita-input"
                                                   onchange="aggiornaQuantita(<?php echo $evento->eid; ?>, this.value)">
                                            <button type="button" class="quantita-btn" onclick="modificaQuantita(<?php echo $evento->eid; ?>, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted">Subtotale: <strong><?php echo number_format($subtotale, 2); ?>€</strong></small>
                                        </div>
                                    </form>

                                    <form method="post" action="" class="mt-2">
                                        <input type="hidden" name="evento_id" value="<?php echo $evento->eid; ?>">
                                        <button type="submit" name="rimuovi_carrello" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Sei sicuro di voler rimuovere questo evento dal carrello?')">
                                            <i class="fas fa-trash-alt me-1"></i> Rimuovi
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-lg-4">
                    <div class="sticky-top" style="top: 20px;">
                        <div class="totale-section">
                            <h4 class="mb-3">Riepilogo ordine</h4>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Eventi (<?php echo count($eventi_carrello); ?>):</span>
                                <span><?php echo number_format($totale_carrello, 2); ?>€</span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Commissione servizio:</span>
                                <span>0,00€</span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between totale-finale">
                                <span>Totale:</span>
                                <span><?php echo number_format($totale_carrello, 2); ?>€</span>
                            </div>
                        </div>

                        <div class="payment-form">
                            <div class="security-info">
                                <i class="fas fa-shield-alt me-2"></i>
                                <strong>Pagamento sicuro</strong><br>
                                <small>I tuoi dati sono protetti con crittografia SSL</small>
                            </div>

                            <form method="post" action="">
                                <h5 class="mb-3">Dati di pagamento</h5>

                                <div class="mb-3">
                                    <label for="nome_carta" class="form-label">Nome sulla carta *</label>
                                    <input type="text" class="form-control" id="nome_carta" name="nome_carta"
                                           placeholder="Mario Rossi" required>
                                </div>

                                <div class="mb-3">
                                    <label for="numero_carta" class="form-label">Numero carta *</label>
                                    <input type="text" class="form-control" id="numero_carta" name="numero_carta"
                                           placeholder="1234 5678 9012 3456" maxlength="19" required>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="scadenza" class="form-label">Scadenza *</label>
                                            <input type="text" class="form-control" id="scadenza" name="scadenza"
                                                   placeholder="MM/YY" maxlength="5" required>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mb-3">
                                            <label for="cvv" class="form-label">CVV *</label>
                                            <input type="text" class="form-control" id="cvv" name="cvv"
                                                   placeholder="123" maxlength="4" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="indirizzo_fatturazione" class="form-label">Indirizzo di fatturazione *</label>
                                    <textarea class="form-control" id="indirizzo_fatturazione" name="indirizzo_fatturazione"
                                              rows="3" placeholder="Via Roma 123, 12345 Milano (MI)" required></textarea>
                                </div>

                                <button type="submit" name="checkout" class="btn btn-primary btn-checkout w-100">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Paga <?php echo number_format($totale_carrello, 2); ?>€
                                </button>

                                <p class="text-center mt-3 small text-muted">
                                    Cliccando "Paga" accetti i nostri termini e condizioni
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Il tuo carrello è vuoto</h3>
                <p class="mb-4">Non hai ancora aggiunto nessun evento al carrello.</p>
                <a href="visite.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Esplora le visite disponibili
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'PageComponenti/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="Function/carrello.js"></script>
</body>
</html>