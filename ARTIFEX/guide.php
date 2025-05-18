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

// Lista delle guide specifiche che vogliamo mostrare
$guide_specifiche = [
    'Francesca Esposito',
    'Sofia Ferrari',
    'Marco Rossi',
    'Giovanni Verdi',
    'Alessandro Russo'
];

// Creiamo una stringa con i nomi per la query IN
$nomi_placeholder = implode(',', array_fill(0, count($guide_specifiche), '?'));

// Costruiamo una lista di parametri per la query
$params = [];
foreach ($guide_specifiche as $nome_completo) {
    $parti = explode(' ', $nome_completo);
    if (count($parti) == 2) {
        $params[] = $parti[0]; // Nome
        $params[] = $parti[1]; // Cognome
    }
}

// Query per selezionare solo le guide specificate
$query = "SELECT DISTINCT g.gid, g.nome, g.cognome, g.luogo_nascita, g.data_nascita, 
          ts.titolo as titolo_studio, g.id_titolo_studio
          FROM guide g
          JOIN titoli_studio ts ON g.id_titolo_studio = ts.tsid
          JOIN conoscenze_linguistiche cl ON g.gid = cl.id_guida
          JOIN lingue l ON cl.id_lingua = l.lid
          JOIN livelli_linguistici ll ON cl.id_livello = ll.llid
          WHERE (g.nome, g.cognome) IN (";

// Aggiungiamo le coppie (nome, cognome) alla query
$placeholders = [];
for ($i = 0; $i < count($guide_specifiche); $i++) {
    $placeholders[] = "(?, ?)";
}
$query .= implode(', ', $placeholders);
$query .= ") ORDER BY g.cognome, g.nome";

// Prepara ed esegui la query
$stmt = $db->prepare($query);
$stmt->execute($params);
$guide = $stmt->fetchAll(PDO::FETCH_OBJ);

// Funzione per ottenere le lingue conosciute da una guida
function getGuideLingue($db, $guida_id) {
    $stmt = $db->prepare("SELECT l.lingua, ll.EQF 
                         FROM conoscenze_linguistiche cl
                         JOIN lingue l ON cl.id_lingua = l.lid
                         JOIN livelli_linguistici ll ON cl.id_livello = ll.llid
                         WHERE cl.id_guida = ?
                         ORDER BY ll.EQF DESC");
    $stmt->execute([$guida_id]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// Funzione per ottenere gli eventi futuri di una guida
function getGuideEventi($db, $guida_id) {
    $stmt = $db->prepare("SELECT e.eid, e.inizio, v.titolo, v.durata_media, si.nome as sito_nome, 
                         si.luogo as sito_luogo, l.lingua, e.prezzo, e.minimo_partecipanti, e.massimo_partecipanti
                         FROM illustrazioni i
                         JOIN eventi e ON i.id_evento = e.eid
                         JOIN visite v ON e.id_visita = v.vid
                         JOIN visite_siti vs ON v.vid = vs.id_visita
                         JOIN siti_interesse si ON vs.id_sito = si.sid
                         JOIN lingue l ON i.id_lingua = l.lid
                         WHERE i.id_guida = ? AND e.inizio > NOW()
                         GROUP BY e.eid
                         ORDER BY e.inizio ASC
                         LIMIT 3");
    $stmt->execute([$guida_id]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
}

// Calcola l'età di una persona
function calcolaEta($data_nascita) {
    $oggi = new DateTime();
    $nascita = new DateTime($data_nascita);
    $diff = $oggi->diff($nascita);
    return $diff->y;
}

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artifex - Le Nostre Guide</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="CSS/index.css" rel="stylesheet">
    <link href="CSS/guide.css" rel="stylesheet">

</head>
<body>
<?php include 'PageComponenti/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container hero-content">
        <h1>Le Nostre Guide Esperte</h1>
        <p class="lead">Scopri i nostri professionisti qualificati che ti accompagneranno in un viaggio attraverso l'arte e la storia d'Italia</p>
    </div>
</section>

<div class="container mb-5">
    <!-- Guide -->
    <?php if (empty($guide)): ?>
        <div class="alert alert-info">
            Nessuna guida trovata.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($guide as $guida):
                $lingue_guida = getGuideLingue($db, $guida->gid);
                $eventi_guida = getGuideEventi($db, $guida->gid);
                $eta = calcolaEta($guida->data_nascita);
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card guide-card">
                        <div class="guide-img">
                            <img src="Immagini/omino.jpg" alt="<?php echo htmlspecialchars($guida->nome . ' ' . $guida->cognome); ?>" class="img-fluid">
                        </div>
                        <div class="guide-info">
                            <h3 class="guide-name"><?php echo htmlspecialchars($guida->nome . ' ' . $guida->cognome); ?></h3>
                            <p class="text-muted"><?php echo htmlspecialchars($guida->titolo_studio); ?></p>
                            <p><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($guida->luogo_nascita); ?> (<?php echo $eta; ?> anni)</p>

                            <h5 class="h6 mt-3">Lingue Parlate</h5>
                            <div class="mb-3">
                                <?php foreach ($lingue_guida as $lingua): ?>
                                    <span class="badge language-badge badge-<?php echo htmlspecialchars($lingua->EQF); ?>">
                                        <?php echo htmlspecialchars($lingua->lingua); ?> (<?php echo htmlspecialchars($lingua->EQF); ?>)
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($eventi_guida)): ?>
                                <h5 class="h6 mt-3">Prossimi Eventi</h5>
                                <div class="event-list">
                                    <?php foreach ($eventi_guida as $evento):
                                        $data_evento = new DateTime($evento->inizio);
                                        ?>
                                        <div class="event-item">
                                            <div class="event-date"><?php echo $data_evento->format('d/m/Y H:i'); ?></div>
                                            <div><?php echo htmlspecialchars($evento->titolo); ?> <span class="badge bg-secondary"><?php echo htmlspecialchars($evento->lingua); ?></span></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($evento->sito_nome); ?>, <?php echo htmlspecialchars($evento->sito_luogo); ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small">Nessun evento programmato</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'PageComponenti/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>