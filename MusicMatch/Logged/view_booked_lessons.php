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

// Aggiorna lo stato delle prenotazioni a "effettuata" se la data della lezione è passata
$update_sql = "UPDATE Prenotazione 
               JOIN Lezione ON Prenotazione.id_lezione = Lezione.id_lezione
               SET Prenotazione.stato_prenotazione = 'effettuata'
               WHERE Prenotazione.id_studente = ? AND Lezione.data_ora < NOW()";
$stmt = $conn->prepare($update_sql);
$stmt->bind_param("i", $id_studente);
$stmt->execute();
$stmt->close();

// Filtraggio per nome dell'insegnante e disciplina
$insegnante_filter = isset($_POST['insegnante']) ? $_POST['insegnante'] : '';
$disciplina_filter = isset($_POST['disciplina']) ? $_POST['disciplina'] : '';

// Recupera tutte le prenotazioni "da fare" fatte dallo studente
$sql = "SELECT Prenotazione.id_prenotazione, Lezione.disciplina, Lezione.data_ora, Lezione.durata, Lezione.prezzo, Insegnante.nome AS nome_insegnante, Insegnante.cognome AS cognome_insegnante, Lezione.id_lezione
        FROM Prenotazione
        JOIN Lezione ON Prenotazione.id_lezione = Lezione.id_lezione
        JOIN Insegnante ON Prenotazione.id_insegnante = Insegnante.id_insegnante
        WHERE Prenotazione.id_studente = ? AND Prenotazione.stato_prenotazione = 'da fare'";

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
$prenotazioni_da_fare = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recupera tutte le prenotazioni disdette dello studente
$sql = "SELECT Prenotazione.id_prenotazione, Lezione.disciplina, Lezione.data_ora, Lezione.durata, Lezione.prezzo, Insegnante.nome AS nome_insegnante, Insegnante.cognome AS cognome_insegnante, Lezione.id_lezione
        FROM Prenotazione
        JOIN Lezione ON Prenotazione.id_lezione = Lezione.id_lezione
        JOIN Insegnante ON Prenotazione.id_insegnante = Insegnante.id_insegnante
        WHERE Prenotazione.id_studente = ? AND Prenotazione.stato_prenotazione = 'disdetta'";

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
$prenotazioni_disdette = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recupera il messaggio di conferma se esiste
$message = isset($message) ? $message : '';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lezioni Prenotate</title>
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
        <h1>Lezioni Prenotate</h1>

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

        <!-- Lezioni Disdette -->
        <h2>Lezioni Disdette</h2>
        <?php if (empty($prenotazioni_disdette)): ?>
            <div class="no-lessons">
                <p>Non hai lezioni disdette.</p>
            </div>
        <?php else: ?>
            <div class="lesson-list">
                <?php foreach ($prenotazioni_disdette as $prenotazione): ?>
                    <div class="lesson-item">
                        <?php $immagine = '../Logged/images/' . htmlspecialchars($prenotazione['disciplina']) . '.jpg'; ?>
                        <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($prenotazione['disciplina']); ?>">
                        <div class="lesson-details">
                            <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($prenotazione['disciplina']); ?></p>
                            <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($prenotazione['data_ora']))); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($prenotazione['durata']); ?> minuti</p>
                            <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($prenotazione['prezzo']); ?></p>
                            <p><strong>Insegnante:</strong> <?php echo htmlspecialchars($prenotazione['nome_insegnante'] . ' ' . $prenotazione['cognome_insegnante']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Lezioni da Fare -->
        <h2>Lezioni da Fare</h2>
        <?php if (empty($prenotazioni_da_fare)): ?>
            <div class="no-lessons">
                <p>Non hai lezioni da fare.</p>
            </div>
        <?php else: ?>
            <div class="lesson-list">
                <?php foreach ($prenotazioni_da_fare as $prenotazione): ?>
                    <div class="lesson-item">
                        <?php $immagine = '../Logged/images/' . htmlspecialchars($prenotazione['disciplina']) . '.jpg'; ?>
                        <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($prenotazione['disciplina']); ?>">
                        <div class="lesson-details">
                            <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($prenotazione['disciplina']); ?></p>
                            <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($prenotazione['data_ora']))); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($prenotazione['durata']); ?> minuti</p>
                            <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($prenotazione['prezzo']); ?></p>
                            <p><strong>Insegnante:</strong> <?php echo htmlspecialchars($prenotazione['nome_insegnante'] . ' ' . $prenotazione['cognome_insegnante']); ?></p>
                            <div class="button-group">
                                <a href="remove_booking.php?id_prenotazione=<?php echo htmlspecialchars($prenotazione['id_prenotazione']); ?>&id_lezione=<?php echo htmlspecialchars($prenotazione['id_lezione']); ?>" class="button remove">Annulla Prenotazione</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <footer>
        <p>&copy; 2024 MusicMatch. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>


