<?php
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Recupera il nome dello studente
$email = $_SESSION['email'];
$sql = "SELECT nome, id_studente FROM Studente WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($nome, $id_studente);
$stmt->fetch();
$stmt->close();

// Recupera tutti gli insegnanti dal database per il menu a discesa
$insegnanti = [];
$sql = "SELECT id_insegnante, CONCAT(nome, ' ', cognome) AS nome_completo FROM Insegnante";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $insegnanti[] = [
            'id_insegnante' => $row['id_insegnante'],
            'nome_completo' => $row['nome_completo']
        ];
    }
} else {
    $message = "Nessun insegnante trovato.";
}

// Recupera l'insegnante selezionato
$id_insegnante = isset($_GET['insegnante']) ? intval($_GET['insegnante']) : 0;

// Recupera il nome completo dell'insegnante e la valutazione media
$nome_completo = "Seleziona un insegnante";
$valutazione_media = 0;
if ($id_insegnante > 0) {
    $sql = "SELECT CONCAT(nome, ' ', cognome) AS nome_completo, 
                   IFNULL(AVG(valutazione), 0) AS valutazione_media 
            FROM Insegnante 
            LEFT JOIN Recensione ON Insegnante.id_insegnante = Recensione.id_insegnante 
            WHERE Insegnante.id_insegnante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_insegnante);
    $stmt->execute();
    $stmt->bind_result($nome_completo, $valutazione_media);
    $stmt->fetch();
    $stmt->close();
}

// Recupera tutte le lezioni prenotabili per l'insegnante selezionato
$lezioni = [];
if ($id_insegnante > 0) {
    $sql = "SELECT id_lezione, disciplina, data_ora, durata, prezzo 
            FROM Lezione 
            WHERE stato_lezione = 'prenotabile' AND id_insegnante = ?
            ORDER BY data_ora ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_insegnante);
    $stmt->execute();
    $lezioni = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $message = "Seleziona un insegnante per visualizzare le lezioni.";
}

// Gestisci la prenotazione della lezione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prenota'])) {
    $id_lezione = isset($_POST['id_lezione']) ? intval($_POST['id_lezione']) : 0;
    $data_prenotazione = date('Y-m-d');
    
    // Verifica se id_lezione e id_insegnante sono validi
    if ($id_lezione > 0 && $id_insegnante > 0) {
        // Inizia una transazione
        $conn->begin_transaction();

        try {
            // Inserisci la prenotazione nel database
            $sql = "INSERT INTO Prenotazione (data_prenotazione, id_studente, id_insegnante, id_lezione) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siii", $data_prenotazione, $id_studente, $id_insegnante, $id_lezione);
            if ($stmt->execute()) {
                // Inserimento riuscito
            } else {
                error_log("Errore inserimento prenotazione: " . $stmt->error);
            }
            
            // Aggiorna lo stato della lezione a "prenotata"
            $sql = "UPDATE Lezione SET stato_lezione = 'prenotata' WHERE id_lezione = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_lezione);
            if ($stmt->execute()) {
                // Aggiornamento riuscito
            } else {
                error_log("Errore aggiornamento stato lezione: " . $stmt->error);
            }
            
            // Commit della transazione
            $conn->commit();
            $stmt->close();

            $message = "Lezione prenotata con successo.";
        } catch (Exception $e) {
            // Rollback della transazione in caso di errore
            $conn->rollback();
            $message = "Errore durante la prenotazione: " . $e->getMessage();
        }
    } else {
        $message = "Dati di prenotazione non validi.";
    }
}

// Recupera il messaggio di conferma se esiste
$message = isset($message) ? $message : '';
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lezioni per Insegnante</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/styles_home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="menu">
                <select id="categoria" onchange="window.location.href='lessons_by_teacher.php?insegnante=' + encodeURIComponent(this.value)">
                    <option value="">Seleziona Insegnante</option>
                    <?php foreach ($insegnanti as $insegnante): ?>
                        <option value="<?php echo htmlspecialchars($insegnante['id_insegnante']); ?>" <?php echo $id_insegnante == $insegnante['id_insegnante'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($insegnante['nome_completo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <a href="student_home.php">Home</a>
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
        <h1>Lezioni disponibili per l'insegnante <?php echo htmlspecialchars($nome_completo); ?> (Valutazione Media: <?php echo htmlspecialchars(number_format($valutazione_media, 1)); ?>)</h1>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="lesson-list">
            <?php if ($lezioni): ?>
                <?php foreach ($lezioni as $lezione_item): ?>
                    <?php
                    $immagine = '../Logged/images/' . htmlspecialchars($lezione_item['disciplina']) . '.jpg';
                    ?>
                    <div class="lesson-item">
                        <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($lezione_item['disciplina']); ?>">
                        <div class="lesson-details">
                            <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($lezione_item['disciplina']); ?></p>
                            <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($lezione_item['data_ora']))); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($lezione_item['durata']); ?> minuti</p>
                            <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($lezione_item['prezzo']); ?></p>
                            <div class="button-group">
                                <form method="post" action="confirm_booking.php">
                                    <input type="hidden" name="id_lezione" value="<?php echo htmlspecialchars($lezione_item['id_lezione']); ?>">
                                    <input type="hidden" name="id_insegnante" value="<?php echo htmlspecialchars($id_insegnante); ?>">
                                    <button type="submit" name="prenota" class="prenota">Prenota</button>
                                </form>
                                <a href="student_home.php?view_details=<?php echo htmlspecialchars($lezione_item['id_lezione']); ?>" class="dettagli">Visualizza Dettagli</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-lessons">
                    <p>Nessuna lezione disponibile per l'insegnante selezionato. <a href="student_home.php">Torna alla Home</a></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (isset($_GET['view_details'])): ?>
            <?php
            $id_lezione = intval($_GET['view_details']);
            $sql = "SELECT * FROM Lezione WHERE id_lezione = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_lezione);
            $stmt->execute();
            $lezione = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            ?>
            <div class="modal">
                <h2>Dettagli Lezione</h2>
                <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($lezione['disciplina']); ?></p>
                <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($lezione['data_ora']))); ?></p>
                <p><strong>Durata:</strong> <?php echo htmlspecialchars($lezione['durata']); ?> minuti</p>
                <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($lezione['prezzo']); ?></p>
                <a href="student_home.php">Chiudi</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
