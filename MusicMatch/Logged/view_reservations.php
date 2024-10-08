<?php
session_start();

// Verifica se l'utente è autenticato e se è un insegnante
if (!isset($_SESSION['email']) || $_SESSION['ruolo'] !== 'insegnante') {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Recupera l'ID dell'insegnante
$email = $_SESSION['email'];
$sql = "SELECT id_insegnante FROM Insegnante WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id_insegnante);
$stmt->fetch();
$stmt->close();

// Filtraggio per livello di abilità e disciplina
$livello_filter = isset($_POST['livello']) ? $_POST['livello'] : '';
$disciplina_filter = isset($_POST['disciplina']) ? $_POST['disciplina'] : '';

// Recupera tutte le lezioni prenotate per questo insegnante che non sono passate e non sono disdette
$sql = "SELECT Prenotazione.id_prenotazione, Lezione.disciplina, Lezione.data_ora, Lezione.durata, Lezione.prezzo, Studente.nome, Studente.cognome, Studente.livello_abilita 
        FROM Prenotazione
        JOIN Lezione ON Prenotazione.id_lezione = Lezione.id_lezione
        JOIN Studente ON Prenotazione.id_studente = Studente.id_studente
        WHERE Lezione.id_insegnante = ? AND Lezione.data_ora >= NOW() AND Prenotazione.stato_prenotazione != 'disdetta'";

if ($livello_filter) {
    $sql .= " AND Studente.livello_abilita = ?";
}
if ($disciplina_filter) {
    $sql .= " AND Lezione.disciplina = ?";
}

$sql .= " ORDER BY Lezione.data_ora ASC";

$stmt = $conn->prepare($sql);
if ($livello_filter && $disciplina_filter) {
    $stmt->bind_param("iss", $id_insegnante, $livello_filter, $disciplina_filter);
} elseif ($livello_filter) {
    $stmt->bind_param("is", $id_insegnante, $livello_filter);
} elseif ($disciplina_filter) {
    $stmt->bind_param("is", $id_insegnante, $disciplina_filter);
} else {
    $stmt->bind_param("i", $id_insegnante);
}
$stmt->execute();
$prenotazioni = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Gestione della cancellazione della prenotazione
if (isset($_POST['cancel_id'])) {
    $cancel_id = $_POST['cancel_id'];
    
    // Verifica lo stato attuale della prenotazione
    $sql = "SELECT stato_prenotazione FROM Prenotazione WHERE id_prenotazione = ? AND id_insegnante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $cancel_id, $id_insegnante);
    $stmt->execute();
    $stmt->bind_result($stato_prenotazione);
    $stmt->fetch();
    $stmt->close();
    
    // Solo aggiorna lo stato se non è già "disdetta"
    if ($stato_prenotazione !== 'disdetta') {
        $sql = "UPDATE Prenotazione SET stato_prenotazione = 'disdetta' WHERE id_prenotazione = ? AND id_insegnante = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cancel_id, $id_insegnante);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lezioni Prenotate</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/styles_home.css">
    <link rel="stylesheet" href="css/styles_review.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="menu">
                <a href="teacher_home.php">Home</a>
                <a href="add_lesson.php">Aggiungi Lezione</a>
                <a href="view_reviews.php">Visualizza Recensioni</a>
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
                <label for="livello">Filtra per Livello di Abilità:</label>
                <select name="livello" id="livello" class="filter-select">
                    <option value="">Tutti</option>
                    <option value="principiante" <?php echo ($livello_filter === 'principiante') ? 'selected' : ''; ?>>Principiante</option>
                    <option value="intermedio" <?php echo ($livello_filter === 'intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                    <option value="avanzato" <?php echo ($livello_filter === 'avanzato') ? 'selected' : ''; ?>>Avanzato</option>
                </select>
                
                <label for="disciplina">Disciplina:</label>
                <input type="text" name="disciplina" id="disciplina" class="filter-input" placeholder="Inserisci disciplina" value="<?php echo htmlspecialchars($disciplina_filter); ?>">

                <button type="submit" class="filter-button">Applica Filtro</button>
            </div>
        </form>

        <?php if (empty($prenotazioni)): ?>
            <div class="no-lessons">
                 <p>Nessuna lezione prenotata al momento con i filtri selezionati.</p>
            </div>
        <?php else: ?>
            <div class="lesson-list">
                <?php foreach ($prenotazioni as $prenotazione): ?>
                    <div class="lesson-item">
                        <?php $immagine = '../Logged/images/' . htmlspecialchars($prenotazione['disciplina']) . '.jpg'; ?>
                        <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($prenotazione['disciplina']); ?>">
                        <div class="lesson-details">
                            <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($prenotazione['disciplina']); ?></p>
                            <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($prenotazione['data_ora']))); ?></p>
                            <p><strong>Durata:</strong> <?php echo htmlspecialchars($prenotazione['durata']); ?> minuti</p>
                            <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($prenotazione['prezzo']); ?></p>
                            <p><strong>Studente:</strong> <?php echo htmlspecialchars($prenotazione['nome'] . ' ' . $prenotazione['cognome']); ?></p>
                            <p><strong>Livello di Abilità:</strong> <?php echo htmlspecialchars($prenotazione['livello_abilita']); ?></p>
                            <div class="button-group">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                    <input type="hidden" name="cancel_id" value="<?php echo htmlspecialchars($prenotazione['id_prenotazione']); ?>">
                                    <button type="submit" class="remove" onclick="return confirm('Sei sicuro di voler disdire questa prenotazione?');">Disdire Prenotazione</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>

