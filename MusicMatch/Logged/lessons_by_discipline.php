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

// Recupera la disciplina selezionata
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';

// Recupera tutte le lezioni prenotabili per la disciplina selezionata
$sql = "SELECT id_lezione, disciplina, data_ora, durata, prezzo, id_insegnante 
        FROM Lezione 
        WHERE stato_lezione = 'prenotabile' AND disciplina = ?
        ORDER BY data_ora ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $categoria);
$stmt->execute();
$lezioni = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Gestisci la prenotazione della lezione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prenota'])) {
    $id_lezione = intval($_POST['id_lezione']);
    $data_prenotazione = date('Y-m-d');
    $id_insegnante = intval($_POST['id_insegnante']);

    // Inizia una transazione
    $conn->begin_transaction();

    try {
        // Inserisci la prenotazione nel database
        $sql = "INSERT INTO Prenotazione (data_prenotazione, id_studente, id_insegnante, id_lezione) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siii", $data_prenotazione, $id_studente, $id_insegnante, $id_lezione);
        $stmt->execute();
        
        // Aggiorna lo stato della lezione a "prenotata"
        $sql = "UPDATE Lezione SET stato_lezione = 'prenotata' WHERE id_lezione = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_lezione);
        $stmt->execute();
        
        // Commit della transazione
        $conn->commit();
        $stmt->close();

        $message = "Lezione prenotata con successo.";
    } catch (Exception $e) {
        // Rollback della transazione in caso di errore
        $conn->rollback();
        $message = "Errore durante la prenotazione: " . $e->getMessage();
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
    <title>Lezioni per Disciplina</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/styles_home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="menu">
                <select id="categoria" onchange="window.location.href='lessons_by_discipline.php?categoria=' + encodeURIComponent(this.value)">
                    <option value="">Seleziona Categoria</option>
                    <?php foreach ($discipline as $disciplina) { ?>
                        <option value="<?php echo htmlspecialchars($disciplina); ?>">
                            <?php echo htmlspecialchars($disciplina); ?>
                        </option>
                    <?php } ?>
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
        <h1>Lezioni per la disciplina: <?php echo htmlspecialchars($categoria); ?></h1>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (empty($lezioni)): ?>
            <div class="no-lessons">
                <p>Nessuna nuova lezione disponibile per la disciplina selezionata. <a href="student_home.php">Torna alla Home</a></p>
            </div>
        <?php else: ?>
            <div class="lesson-list">
                <?php foreach ($lezioni as $lezione): ?>
                    <div class="lesson-item">
                        <?php $immagine = '../Logged/images/' . htmlspecialchars($lezione['disciplina']) . '.jpg';?>
                        <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($lezione['disciplina']); ?>">
                        <div class="lesson-details">
                            <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($lezione['disciplina']); ?></p>
                            <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($lezione['data_ora']))); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($lezione['durata']); ?> minuti</p>
                            <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($lezione['prezzo']); ?></p>
                            <div class="button-group">
                                <form method="post" action="confirm_booking.php">
                                    <input type="hidden" name="id_lezione" value="<?php echo htmlspecialchars($lezione['id_lezione']); ?>">
                                    <input type="hidden" name="id_insegnante" value="<?php echo htmlspecialchars($lezione['id_insegnante']); ?>">
                                    <button type="submit" name="prenota" class="prenota">Prenota</button>
                                </form>
                                <a href="student_home.php?view_details=<?php echo htmlspecialchars($lezione['id_lezione']); ?>" class="dettagli">Visualizza Dettagli</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
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
