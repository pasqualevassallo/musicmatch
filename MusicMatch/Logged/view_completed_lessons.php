<?php
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Recupera il nome dello studente e il suo ID
$email = $_SESSION['email'];
$sql = "SELECT nome, id_studente FROM Studente WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($nome, $id_studente);
$stmt->fetch();
$stmt->close();

// Filtraggio per nome dell'insegnante e disciplina
$insegnante_filter = isset($_POST['insegnante']) ? $_POST['insegnante'] : '';
$disciplina_filter = isset($_POST['disciplina']) ? $_POST['disciplina'] : '';

// Recupera tutte le prenotazioni di lezioni completate dallo studente
$sql = "SELECT Prenotazione.id_prenotazione, Lezione.disciplina, Lezione.data_ora, Lezione.durata, Lezione.prezzo, Insegnante.nome AS nome_insegnante, Insegnante.cognome AS cognome_insegnante, Lezione.id_lezione, Insegnante.id_insegnante
        FROM Prenotazione
        JOIN Lezione ON Prenotazione.id_lezione = Lezione.id_lezione
        JOIN Insegnante ON Prenotazione.id_insegnante = Insegnante.id_insegnante
        WHERE Prenotazione.id_studente = ? AND Lezione.data_ora < NOW()";

// Aggiungi i filtri nella query SQL
if ($insegnante_filter) {
    $sql .= " AND (Insegnante.nome LIKE ? OR Insegnante.cognome LIKE ?)";
}

if ($disciplina_filter) {
    $sql .= " AND Lezione.disciplina LIKE ?";
}

$sql .= " ORDER BY Lezione.data_ora ASC";

$stmt = $conn->prepare($sql);

// Prepara i parametri da passare alla query
if ($insegnante_filter && $disciplina_filter) {
    $search_filter_insegnante = "%$insegnante_filter%";
    $search_filter_disciplina = "%$disciplina_filter%";
    $stmt->bind_param("isss", $id_studente, $search_filter_insegnante, $search_filter_insegnante, $search_filter_disciplina);
} elseif ($insegnante_filter) {
    $search_filter_insegnante = "%$insegnante_filter%";
    $stmt->bind_param("iss", $id_studente, $search_filter_insegnante, $search_filter_insegnante);
} elseif ($disciplina_filter) {
    $search_filter_disciplina = "%$disciplina_filter%";
    $stmt->bind_param("is", $id_studente, $search_filter_disciplina);
} else {
    $stmt->bind_param("i", $id_studente);
}

$stmt->execute();
$prenotazioni = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Inizializza gli array per le lezioni recensite e non recensite
$lezioni_recensite = [];
$lezioni_non_recensite = [];

// Ciclo per dividere le lezioni in recensite e non recensite
foreach ($prenotazioni as $prenotazione) {
    $recensione_testo = null;
    $recensione_risposta = null;

    // Controlla se esiste già una recensione per questa lezione
    $sql = "SELECT testo, risposta FROM Recensione WHERE id_studente = ? AND id_lezione = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $id_studente, $prenotazione['id_lezione']);
    $stmt->execute();
    $stmt->bind_result($recensione_testo, $recensione_risposta);
    $stmt->fetch();
    $stmt->close();

    // Aggiungi la lezione all'array corretto
    if ($recensione_testo) {
        $lezioni_recensite[] = array_merge($prenotazione, ['recensione_testo' => $recensione_testo, 'recensione_risposta' => $recensione_risposta]);
    } else {
        $lezioni_non_recensite[] = $prenotazione;
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
    <title>Lezioni Effettuate</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/styles_home.css">
    <link rel="stylesheet" href="css/styles_response.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="menu">
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
        <h1>Lezioni Effettuate</h1>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="filter-form">
            <div class="filter-group">
                <label for="insegnante">Filtra per Nome Insegnante:</label>
                <input type="text" name="insegnante" id="insegnante" class="filter-select" value="<?php echo htmlspecialchars($insegnante_filter); ?>">
                
                <label for="disciplina">Filtra per Disciplina:</label>
                <input type="text" name="disciplina" id="disciplina" class="filter-select" value="<?php echo htmlspecialchars($disciplina_filter); ?>">

                <button type="submit" class="filter-button">Applica Filtro</button>
            </div>
        </form>

        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Lezioni da Recensire -->
        <h2>Lezioni da Recensire</h2>
        <?php if (empty($lezioni_non_recensite)): ?>
            <div class="no-lessons">
                <p>Non hai lezioni da recensire. <a href="student_home.php">Prenota una lezione</a></p>
            </div>
        <?php else: ?>
            <div class="lesson-list">
                <?php foreach ($lezioni_non_recensite as $lezione): ?>
                    <div class="lesson-item">
                        <?php $immagine = '../Logged/images/' . htmlspecialchars($lezione['disciplina']) . '.jpg'; ?>
                        <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($lezione['disciplina']); ?>">
                        <div class="lesson-details">
                            <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($lezione['disciplina']); ?></p>
                            <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($lezione['data_ora']))); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($lezione['durata']); ?> minuti</p>
                            <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($lezione['prezzo']); ?></p>
                            <p><strong>Insegnante:</strong> <?php echo htmlspecialchars($lezione['nome_insegnante'] . ' ' . $lezione['cognome_insegnante']); ?></p>
                            <div class="button-group">
                                <a href="write_review.php?id_lezione=<?php echo htmlspecialchars($lezione['id_lezione']); ?>&id_insegnante=<?php echo htmlspecialchars($lezione['id_insegnante']); ?>" class="dettagli">Scrivi Recensione</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Lezioni Recensite -->
        <h2>Lezioni Recensite</h2>
        <?php if (empty($lezioni_recensite)): ?>
            <div class="no-lessons">
                <p>Non hai lezioni recensite. <a href="student_home.php">Prenota una lezione</a></p>
            </div>
        <?php else: ?>
            <div class="lesson-list">
                <?php foreach ($lezioni_recensite as $lezione): ?>
                    <div class="lesson-item">
                        <?php $immagine = '../Logged/images/' . htmlspecialchars($lezione['disciplina']) . '.jpg'; ?>
                        <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($lezione['disciplina']); ?>">
                        <div class="lesson-details">
                            <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($lezione['disciplina']); ?></p>
                            <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($lezione['data_ora']))); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($lezione['durata']); ?> minuti</p>
                            <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($lezione['prezzo']); ?></p>
                            <p><strong>Insegnante:</strong> <?php echo htmlspecialchars($lezione['nome_insegnante'] . ' ' . $lezione['cognome_insegnante']); ?></p>
                            <div class="review-container">
                                <div class="review-text">
                                    <strong>Recensione:</strong> <?php echo htmlspecialchars($lezione['recensione_testo']); ?>
                                </div>

                                <?php if ($lezione['recensione_risposta']): ?>
                                    <div class="review-response">
                                        <strong>Risposta dell'Insegnante:</strong> <?php echo htmlspecialchars($lezione['recensione_risposta']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
