=<?php
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

// Gestione aggiornamento quantità
if (isset($_POST['aggiorna_quantita'])) {
    try {
        $carrello_id = (int)$_POST['carrello_id'];
        $nuova_quantita = (int)$_POST['nuova_quantita'];

        if ($nuova_quantita > 0) {
            // Verifica disponibilità posti
            $sql_check = "SELECT e.massimo_partecipanti, 
                         (SELECT COALESCE(SUM(o.quantita), 0) FROM ordini o WHERE o.id_evento = e.eid) as posti_prenotati
                         FROM carrello c
                         JOIN eventi e ON c.id_evento = e.eid
                         WHERE c.cid = ? AND c.id_turista = ?";
            $stmt_check = $db->prepare($sql_check);
            $stmt_check->execute([$carrello_id, $user_id]);
            $evento = $stmt_check->fetch(PDO::FETCH_OBJ);

            if ($evento && ($evento->posti_prenotati + $nuova_quantita) <= $evento->massimo_partecipanti) {
                $sql_update = "UPDATE carrello SET quantita = ? WHERE cid = ? AND id_turista = ?";
                $stmt_update = $db->prepare($sql_update);
                $stmt_update->execute([$nuova_quantita, $carrello_id, $user_id]);
                $messaggio = "Quantità aggiornata con successo!";
                $tipo_messaggio = "success";
            } else {
                $messaggio = "Non ci sono abbastanza posti disponibili per questa quantità.";
                $tipo_messaggio = "danger";
            }
        } else {
            $messaggio = "La quantità deve essere maggiore di 0.";
            $tipo_messaggio = "danger";
        }
    } catch (Exception $e) {
        $messaggio = "Errore durante l'aggiornamento: " . $e->getMessage();
        $tipo_messaggio = "danger";
    }
}

// Gestione rimozione elemento dal carrello
if (isset($_POST['rimuovi_elemento'])) {
    try {
        $carrello_id = (int)$_POST['carrello_id'];

        $sql_delete = "DELETE FROM carrello WHERE cid = ? AND id_turista = ?";
        $stmt_delete = $db->prepare($sql_delete);
        $stmt_delete->execute([$carrello_id, $user_id]);

        $messaggio = "Elemento rimosso dal carrello!";
        $tipo_messaggio = "success";
    } catch (Exception $e) {
        $messaggio = "Errore durante la rimozione: " . $e->getMessage();
        $tipo_messaggio = "danger";
    }
}

// Gestione pagamento
if (isset($_POST['procedi_pagamento'])) {
    try {
        // Validazione dati carta di credito (base)
        $numero_carta = preg_replace('/\s+/', '', $_POST['numero_carta']);
        $nome_carta = trim($_POST['nome_carta']);
        $scadenza = $_POST['scadenza'];
        $cvv = $_POST['cvv'];

        // Validazioni di base
        if (strlen($numero_carta) < 13 || strlen($numero_carta) > 19) {
            throw new Exception("Numero carta non valido.");
        }

        if (empty($nome_carta)) {
            throw new Exception("Nome sulla carta richiesto.");
        }

        if (strlen($cvv) < 3 || strlen($cvv) > 4) {
            throw new Exception("CVV non valido.");
        }

        // Verifica scadenza
        $scadenza_parts = explode('/', $scadenza);
        if (count($scadenza_parts) != 2) {
            throw new Exception("Formato scadenza non valido.");
        }

        $mese = (int)$scadenza_parts[0];
        $anno = (int)$scadenza_parts[1] + 2000;

        if ($mese < 1 || $mese > 12) {
            throw new Exception("Mese di scadenza non valido.");
        }

        $data_scadenza = new DateTime("$anno-$mese-01");
        $oggi = new DateTime();

        if ($data_scadenza < $oggi) {
            throw new Exception("La carta di credito è scaduta.");
        }

        // Ottieni elementi del carrello
        $sql_carrello = "SELECT c.cid, c.id_evento, c.quantita, e.prezzo, e.massimo_partecipanti,
                        (SELECT COALESCE(SUM(o.quantita), 0) FROM ordini o WHERE o.id_evento = e.eid) as posti_prenotati
                        FROM carrello c
                        JOIN eventi e ON c.id_evento = e.eid
                        WHERE c.id_turista = ?";
        $stmt_carrello = $db->prepare($sql_carrello);
        $stmt_carrello->execute([$user_id]);
        $elementi_carrello = $stmt_carrello->fetchAll(PDO::FETCH_OBJ);

        if (empty($elementi_carrello)) {
            throw new Exception("Il carrello è vuoto.");
        }

        // Inizia transazione
        $db->beginTransaction();

        // Verifica disponibilità per tutti gli eventi
        foreach ($elementi_carrello as $elemento) {
            if (($elemento->posti_prenotati + $elemento->quantita) > $elemento->massimo_partecipanti) {
                throw new Exception("Non ci sono abbastanza posti disponibili per uno degli eventi nel carrello.");
            }

            // Verifica se l'utente ha già prenotato questo evento
            $sql_check_ordine = "SELECT oid FROM ordini WHERE id_evento = ? AND id_turista = ?";
            $stmt_check_ordine = $db->prepare($sql_check_ordine);
            $stmt_check_ordine->execute([$elemento->id_evento, $user_id]);
            if ($stmt_check_ordine->fetch()) {
                throw new Exception("Hai già prenotato uno degli eventi nel carrello.");
            }
        }

        // Crea ordini per tutti gli elementi del carrello
        foreach ($elementi_carrello as $elemento) {
            $sql_ordine = "INSERT INTO ordini (id_evento, id_turista, data, quantita) VALUES (?, ?, NOW(), ?)";
            $stmt_ordine = $db->prepare($sql_ordine);
            $stmt_ordine->execute([$elemento->id_evento, $user_id, $elemento->quantita]);
        }

        // Svuota il carrello
        $sql_svuota = "DELETE FROM carrello WHERE id_turista = ?";
        $stmt_svuota = $db->prepare($sql_svuota);
        $stmt_svuota->execute([$user_id]);

        // Commit transazione
        $db->commit();

        $messaggio = "Pagamento completato con successo! Le tue prenotazioni sono state confermate.";
        $tipo_messaggio = "success";

    } catch (Exception $e) {
        $db->rollback();
        $messaggio = "Errore durante il pagamento: " . $e->getMessage();
        $tipo_messaggio = "danger";
    }
}

// Query per ottenere gli elementi del carrello
$sql = "SELECT c.cid, c.quantita, c.id_evento, e.prezzo, e.inizio, 
        v.titolo as visita_titolo, v.vid as visita_id,
        g.nome as guida_nome, g.cognome as guida_cognome,
        l.lingua,
        (SELECT COALESCE(SUM(o.quantita), 0) FROM ordini o WHERE o.id_evento = e.eid) as posti_prenotati,
        e.massimo_partecipanti
        FROM carrello c
        JOIN eventi e ON c.id_evento = e.eid
        JOIN visite v ON e.id_visita = v.vid
        JOIN illustrazioni i ON e.eid = i.id_evento
        JOIN guide g ON i.id_guida = g.gid
        JOIN lingue l ON i.id_lingua = l.lid
        WHERE c.id_turista = ?
        ORDER BY e.inizio ASC";

$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$elementi_carrello = $stmt->fetchAll(PDO::FETCH_OBJ);

// Calcola il totale
$totale = 0;
foreach ($elementi_carrello as $elemento) {
    $totale += $elemento->prezzo * $elemento->quantita;
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
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="visite.php">Visite Guidate</a></li>
            <li class="breadcrumb-item active">Carrello</li>
        </ol>
    </nav>

    <h1 class="mb-4">
        <i class="fas fa-shopping-cart me-2"></i>Il tuo carrello
        <?php if (!empty($elementi_carrello)): ?>
            <span class="badge bg-primary"><?php echo count($elementi_carrello); ?></span>
        <?php endif; ?>
    </h1>

    <?php if (!empty($messaggio)): ?>
        <div class="alert alert-<?php echo $tipo_messaggio; ?> alert-dismissible fade show" role="alert">
            <?php echo $messaggio; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($elementi_carrello)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Il tuo carrello è vuoto</h3>
            <p class="mb-4">Non hai ancora aggiunto nessuna visita al carrello.</p>
            <a href="visite.php" class="btn btn-primary btn-lg">
                <i class="fas fa-search me-2"></i>Esplora le visite
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Colonna elementi carrello -->
            <div class="col-lg-8">
                <?php foreach ($elementi_carrello as $elemento):
                    $data_evento = new DateTime($elemento->inizio);
                    $data_formattata = $data_evento->format('d/m/Y H:i');
                    $posti_disponibili = $elemento->massimo_partecipanti - $elemento->posti_prenotati;
                    $subtotale = $elemento->prezzo * $elemento->quantita;
                    ?>
                    <div class="carrello-item p-4 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="mb-1">
                                    <a href="dettaglio_visita.php?id=<?php echo $elemento->visita_id; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($elemento->visita_titolo); ?>
                                    </a>
                                </h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-calendar me-1"></i> <?php echo $data_formattata; ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-user me-1"></i> Guida: <?php echo htmlspecialchars($elemento->guida_nome . ' ' . $elemento->guida_cognome); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-language me-1"></i> <?php echo htmlspecialchars($elemento->lingua); ?>
                                </p>
                                <?php if ($posti_disponibili < $elemento->quantita): ?>
                                    <div class="alert alert-warning mt-2 mb-0 py-1 px-2 small">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Attenzione: rimangono solo <?php echo $posti_disponibili; ?> posti disponibili
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-3">
                                <div class="d-flex align-items-center justify-content-center">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="carrello_id" value="<?php echo $elemento->cid; ?>">
                                        <input type="hidden" name="nuova_quantita" value="<?php echo max(1, $elemento->quantita - 1); ?>">
                                        <button type="submit" name="aggiorna_quantita" class="btn-quantity me-2" <?php echo $elemento->quantita <= 1 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </form>

                                    <input type="number" class="quantity-input" value="<?php echo $elemento->quantita; ?>" min="1" max="<?php echo $posti_disponibili; ?>" readonly>

                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="carrello_id" value="<?php echo $elemento->cid; ?>">
                                        <input type="hidden" name="nuova_quantita" value="<?php echo $elemento->quantita + 1; ?>">
                                        <button type="submit" name="aggiorna_quantita" class="btn-quantity ms-2" <?php echo $elemento->quantita >= $posti_disponibili ? 'disabled' : ''; ?>>
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="col-md-2 text-center">
                                <p class="mb-1"><strong><?php echo number_format($subtotale, 2); ?>€</strong></p>
                                <small class="text-muted"><?php echo number_format($elemento->prezzo, 2); ?>€ cad.</small>
                            </div>

                            <div class="col-md-1 text-center">
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="carrello_id" value="<?php echo $elemento->cid; ?>">
                                    <button type="submit" name="rimuovi_elemento" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('Sei sicuro di voler rimuovere questo elemento dal carrello?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="d-flex justify-content-between mt-4">
                    <a href="visite.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Continua ad esplorare
                    </a>
                    <button type="button" class="btn btn-outline-danger" onclick="if(confirm('Sei sicuro di voler svuotare tutto il carrello?')) { window.location.href='?svuota_carrello=1'; }">
                        <i class="fas fa-trash me-2"></i>Svuota carrello
                    </button>
                </div>
            </div>

            <!-- Colonna pagamento -->
            <div class="col-lg-4">
                <div class="totale-box p-4 mb-4">
                    <h4 class="mb-3">Riepilogo ordine</h4>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotale:</span>
                        <span><?php echo number_format($totale, 2); ?>€</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Commissioni:</span>
                        <span>0.00€</span>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between">
                        <strong>Totale:</strong>
                        <strong><?php echo number_format($totale, 2); ?>€</strong>
                    </div>
                </div>

                <div class="form-pagamento p-4">
                    <h5 class="mb-4">
                        <i class="fas fa-credit-card me-2"></i>Dati di pagamento
                    </h5>

                    <form method="post" action="" id="form-pagamento">
                        <div class="mb-3">
                            <label for="numero_carta" class="form-label">Numero carta</label>
                            <input type="text" class="form-control card-input" id="numero_carta" name="numero_carta"
                                   placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>

                        <div class="mb-3">
                            <label for="nome_carta" class="form-label">Nome sulla carta</label>
                            <input type="text" class="form-control" id="nome_carta" name="nome_carta"
                                   placeholder="Mario Rossi" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="scadenza" class="form-label">Scadenza</label>
                                <input type="text" class="form-control" id="scadenza" name="scadenza"
                                       placeholder="MM/AA" maxlength="5" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" name="cvv"
                                       placeholder="123" maxlength="4" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="accetto_termini" required>
                                <label class="form-check-label" for="accetto_termini">
                                    Accetto i <a href="#" class="text-primary">termini e condizioni</a>
                                </label>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="procedi_pagamento" class="btn btn-success btn-lg">
                                <i class="fas fa-lock me-2"></i>Paga <?php echo number_format($totale, 2); ?>€
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                I tuoi dati sono protetti con crittografia SSL
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'PageComponenti/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
// Gestione svuotamento carrello
if (isset($_GET['svuota_carrello'])) {
    try {
        $sql_svuota = "DELETE FROM carrello WHERE id_turista = ?";
        $stmt_svuota = $db->prepare($sql_svuota);
        $stmt_svuota->execute([$user_id]);
        header('Location: carrello.php?msg=carrello_svuotato');
        exit;
    } catch (Exception $e) {
        $messaggio = "Errore durante lo svuotamento del carrello.";
        $tipo_messaggio = "danger";
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'carrello_svuotato') {
    echo "<script>window.onload = function() { 
        document.querySelector('.container').innerHTML = '<div class=\"empty-cart\"><i class=\"fas fa-shopping-cart\"></i><h3>Carrello svuotato</h3><p class=\"mb-4\">Il tuo carrello è stato svuotato con successo.</p><a href=\"visite.php\" class=\"btn btn-primary btn-lg\"><i class=\"fas fa-search me-2\"></i>Esplora le visite</a></div>'; 
    }</script>";
}
?>

</body>
</html>