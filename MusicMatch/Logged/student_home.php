<?php
session_start();

// Abilita la visualizzazione degli errori per il debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se l'utente è autenticato
if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Aggiorna lo stato delle lezioni scadute
$current_time = date('Y-m-d H:i:s');
$update_stmt = $conn->prepare("UPDATE Lezione SET stato_lezione = 'scaduta' WHERE stato_lezione = 'prenotabile' AND data_ora < ?");
$update_stmt->bind_param("s", $current_time);
$update_stmt->execute();
$update_stmt->close();

// Recupera il nome dello studente
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT nome, id_studente FROM Studente WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($nome, $id_studente);
$stmt->fetch();
$stmt->close();

// Recupera tutte le discipline dal database per il menu a discesa
$discipline = [];
$sql = "SELECT nome FROM Disciplina";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $discipline[] = $row['nome'];
    }
} else {
    $message = "Nessuna disciplina trovata.";
}

// Recupera tutti gli insegnanti dal database per il menu a discesa
$insegnanti = [];
$sql = "SELECT id_insegnante, CONCAT(nome, ' ', cognome) AS nome_completo FROM Insegnante";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $insegnanti[] = $row;
    }
} else {
    $message = "Nessun insegnante trovato.";
}

// Recupera tutte le lezioni prenotabili in ordine crescente di data e ora
$stmt = $conn->query("SELECT id_lezione, disciplina, data_ora, durata, prezzo, id_insegnante FROM Lezione WHERE stato_lezione = 'prenotabile' ORDER BY data_ora ASC");
$lezioni = $stmt->fetch_all(MYSQLI_ASSOC);

// Gestisci la visualizzazione dei dettagli della lezione
$lezione = null;
$media_valutazioni = 0;
$nome_insegnante = '';
$cognome_insegnante = '';
$email_insegnante = '';

if (isset($_GET['view_details'])) {
    $id_lezione = intval($_GET['view_details']);
    
    // Recupera i dettagli della lezione
    $stmt = $conn->prepare("
        SELECT id_lezione, disciplina, data_ora, durata, prezzo, id_insegnante
        FROM Lezione 
        WHERE id_lezione = ?
    ");
    $stmt->bind_param("i", $id_lezione);
    $stmt->execute();
    $lezione = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($lezione) {
        // Recupera i dettagli dell'insegnante usando l'id_insegnante dalla lezione
        $stmt = $conn->prepare("
            SELECT nome, cognome, email
            FROM Insegnante
            WHERE id_insegnante = ?
        ");
        $stmt->bind_param("i", $lezione['id_insegnante']);
        $stmt->execute();
        $insegnante = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Verifica che i dati dell'insegnante siano presenti
        $nome_insegnante = isset($insegnante['nome']) ? $insegnante['nome'] : 'Non disponibile';
        $cognome_insegnante = isset($insegnante['cognome']) ? $insegnante['cognome'] : 'Non disponibile';
        $email_insegnante = isset($insegnante['email']) ? $insegnante['email'] : 'Non disponibile';

        // Calcola la media delle valutazioni dell'insegnante
        $stmt = $conn->prepare("SELECT AVG(valutazione) as media_valutazioni FROM Recensione WHERE id_insegnante = ?");
        $stmt->bind_param("i", $lezione['id_insegnante']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $media_valutazioni = $result['media_valutazioni'] ? $result['media_valutazioni'] : 0;
        $stmt->close();
    } else {
        // Gestisci il caso in cui la lezione non esista
        $message = 'Lezione non trovata.';
    }
}

$message = isset($message) ? $message : '';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Studente</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/styles_home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="menu">
            <select class="custom-select" id="categoria" onchange="window.location.href='lessons_by_discipline.php?categoria=' + encodeURIComponent(this.value)">
                    <option value="">Seleziona Disciplina</option>
                    <?php foreach ($discipline as $disciplina) { ?>
                        <option value="<?php echo htmlspecialchars($disciplina); ?>">
                            <?php echo htmlspecialchars($disciplina); ?>
                        </option>
                    <?php } ?>
                </select>
                
                <select class="custom-select" id="categoria" onchange="window.location.href='lessons_by_teacher.php?insegnante=' + encodeURIComponent(this.value)">
                    <option value="">Seleziona Insegnante</option>
                    <?php foreach ($insegnanti as $insegnante) { ?>
                        <option value="<?php echo htmlspecialchars($insegnante['id_insegnante']); ?>">
                            <?php echo htmlspecialchars($insegnante['nome_completo']); ?>
                        </option>
                    <?php } ?>
                </select>
                
                <a href="view_booked_lessons.php">Visualizza Lezioni Prenotate</a>
                <a href="view_completed_lessons.php">Visualizza Lezioni Effettuate</a>
            </div>
            
            <div class="profile">
                <a href="../Logged/personal_area.php"><i class="fas fa-user"></i> Area Personale</a>
                <a href="Logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>
    <main>
    <h1>Benvenuto, <?php echo htmlspecialchars($nome); ?>! Dai un'occhiata alle ultime lezioni aggiunte :</h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="lesson-list">
            <?php if ($lezioni): ?>
                <?php foreach ($lezioni as $lezione_item): ?>
                    <div class="lesson-item">
                    <?php
                        $immagine = '../Logged/images/' . htmlspecialchars($lezione_item['disciplina']) . '.jpg';
                        ?>
                        <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($lezione_item['disciplina']); ?>">
                        <div class="lesson-details">
                            <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($lezione_item['disciplina']); ?></p>
                            <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($lezione_item['data_ora']))); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($lezione_item['durata']); ?> minuti</p>
                            <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($lezione_item['prezzo']); ?></p>
                            <div class="button-group">
                                <form method="post" action="confirm_booking.php">
                                    <input type="hidden" name="id_lezione" value="<?php echo htmlspecialchars($lezione_item['id_lezione']); ?>">
                                    <input type="hidden" name="id_insegnante" value="<?php echo htmlspecialchars($lezione_item['id_insegnante']); ?>">
                                    <button type="submit" name="prenota" class="prenota">Prenota</button>
                                </form>
                                <a href="student_home.php?view_details=<?php echo htmlspecialchars($lezione_item['id_lezione']); ?>" class="dettagli">Visualizza Dettagli</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Nessuna nuova lezione disponibile al momento.</p>
            <?php endif; ?>
        </div>
    </main>

    <!-- Pop-up per visualizzare i dettagli della lezione -->
    <?php if ($lezione): ?>
        <div id="modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Dettagli Lezione</h2>
                <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($lezione['disciplina']); ?></p>
                <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($lezione['data_ora']))); ?></p>
                <p><strong>Durata:</strong> <?php echo htmlspecialchars($lezione['durata']); ?> minuti</p>
                <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($lezione['prezzo']); ?></p>
                <p><strong>Insegnante:</strong> <?php echo htmlspecialchars($nome_insegnante . ' ' . $cognome_insegnante); ?></p>
                <p><strong>Email Insegnante:</strong> <?php echo htmlspecialchars($email_insegnante); ?></p>
                <p><strong>Media Valutazioni Insegnante:</strong> <?php echo htmlspecialchars(number_format($media_valutazioni, 2)); ?> / 5.00</p>
            </div>
        </div>
    <?php endif; ?>

    <script src="js/popup.js"></script>
</body>
</html>