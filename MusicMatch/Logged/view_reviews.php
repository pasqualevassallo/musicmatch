<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Recupera l'ID dell'insegnante loggato
$email = $_SESSION['email'];
$sql = "SELECT id_insegnante, nome FROM Insegnante WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id_insegnante, $nome);
$stmt->fetch();
$stmt->close();

// Controlla se l'insegnante ha inviato una risposta
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['risposta']) && isset($_POST['id_recensione'])) {
    $risposta = $_POST['risposta'];
    $id_recensione = $_POST['id_recensione'];

    // Aggiorna la recensione con la risposta dell'insegnante
    $sql = "UPDATE Recensione SET risposta = ? WHERE id_recensione = ? AND id_insegnante = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $risposta, $id_recensione, $id_insegnante);
    
    if ($stmt->execute()) {
        echo "<p>Risposta inviata con successo!</p>";
    } else {
        echo "<p>Errore durante l'invio della risposta.</p>";
    }
    $stmt->close();
}

// Recupera i filtri 
$student_filter = isset($_POST['studente']) ? $_POST['studente'] : '';
$discipline_filter = isset($_POST['disciplina']) ? $_POST['disciplina'] : '';
$rating_filter = isset($_POST['valutazione']) ? $_POST['valutazione'] : '';

// Costruisci la query con i filtri
$sql = "SELECT Recensione.id_recensione, Recensione.testo, Recensione.valutazione, Recensione.risposta, Lezione.disciplina, Studente.nome, Studente.cognome 
        FROM Recensione
        JOIN Lezione ON Recensione.id_lezione = Lezione.id_lezione
        JOIN Studente ON Recensione.id_studente = Studente.id_studente
        WHERE Recensione.id_insegnante = ?";

if (!empty($student_filter)) {
    $sql .= " AND (Studente.nome LIKE ? OR Studente.cognome LIKE ?)";
}
if (!empty($discipline_filter)) {
    $sql .= " AND Lezione.disciplina = ?";
}
if (!empty($rating_filter)) {
    $sql .= " AND Recensione.valutazione = ?";
}

$stmt = $conn->prepare($sql);

// Binding dei parametri di filtro (come sopra)
if (!empty($student_filter) && !empty($discipline_filter) && !empty($rating_filter)) {
    $student_filter = "%$student_filter%";
    $stmt->bind_param("isssi", $id_insegnante, $student_filter, $student_filter, $discipline_filter, $rating_filter);
} elseif (!empty($student_filter) && !empty($discipline_filter)) {
    $student_filter = "%$student_filter%";
    $stmt->bind_param("isss", $id_insegnante, $student_filter, $student_filter, $discipline_filter);
} elseif (!empty($student_filter) && !empty($rating_filter)) {
    $student_filter = "%$student_filter%";
    $stmt->bind_param("issi", $id_insegnante, $student_filter, $student_filter, $rating_filter);
} elseif (!empty($discipline_filter) && !empty($rating_filter)) {
    $stmt->bind_param("isi", $id_insegnante, $discipline_filter, $rating_filter);
} elseif (!empty($student_filter)) {
    $student_filter = "%$student_filter%";
    $stmt->bind_param("iss", $id_insegnante, $student_filter, $student_filter);
} elseif (!empty($discipline_filter)) {
    $stmt->bind_param("is", $id_insegnante, $discipline_filter);
} elseif (!empty($rating_filter)) {
    $stmt->bind_param("ii", $id_insegnante, $rating_filter);
} else {
    $stmt->bind_param("i", $id_insegnante);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizza Recensioni</title>
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
                <a href="view_reservations.php">Visualizza Prenotazioni</a>
            </div>
            <div class="profile">
                <a href="../Logged/personal_area.php"><i class="fas fa-user"></i> Area Personale</a>
                <a href="Logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <h1>Recensioni per le tue lezioni</h1>
        <!-- Modulo di filtro -->
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="filter-form">
            <div class="filter-group">
                <label for="studente">Nome Studente:</label>
                <input type="text" name="studente" id="studente" class="filter-input" placeholder="Inserisci nome studente" value="<?php echo htmlspecialchars($student_filter); ?>">

                <label for="disciplina">Disciplina:</label>
                <input type="text" name="disciplina" id="disciplina" class="filter-input" placeholder="Inserisci disciplina" value="<?php echo htmlspecialchars($discipline_filter); ?>">

                <label for="valutazione">Valutazione:</label>
                <select name="valutazione" id="valutazione" class="filter-select">
                    <option value="">Tutte</option>
                    <option value="1" <?php echo ($rating_filter === '1') ? 'selected' : ''; ?>>1</option>
                    <option value="2" <?php echo ($rating_filter === '2') ? 'selected' : ''; ?>>2</option>
                    <option value="3" <?php echo ($rating_filter === '3') ? 'selected' : ''; ?>>3</option>
                    <option value="4" <?php echo ($rating_filter === '4') ? 'selected' : ''; ?>>4</option>
                    <option value="5" <?php echo ($rating_filter === '5') ? 'selected' : ''; ?>>5</option>
                </select>

                <button type="submit" class="filter-button">Applica Filtro</button>
            </div>
        </form>

        <div class="reviews">
            <?php if ($result->num_rows > 0) { ?>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <div class="review-container">
                        <div class="review-header">
                            <h3>Recensione di <?php echo htmlspecialchars($row['nome'] . ' ' . $row['cognome']); ?> - <?php echo htmlspecialchars($row['disciplina']); ?></h3>
                            <div class="rating">Valutazione: <?php echo str_repeat('★', $row['valutazione']); ?></div>
                        </div>
                        <div class="review-body">
                            <p><?php echo htmlspecialchars($row['testo']); ?></p>
                        </div>

                        <!-- Se la risposta è già stata data, mostriamo la risposta -->
                        <?php if (!empty($row['risposta'])) { ?>
                            <div class="response-container">
                                <p><strong>Risposta:</strong> <?php echo htmlspecialchars($row['risposta']); ?></p>
                            </div>
                        <?php } else { ?>
                            <!-- Form per inviare la risposta -->
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                <textarea name="risposta" class="response-input" placeholder="Scrivi una risposta..."></textarea>
                                <input type="hidden" name="id_recensione" value="<?php echo $row['id_recensione']; ?>">
                                <button type="submit" class="response-button">Invia Risposta</button>
                            </form>
                        <?php } ?>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p class="no-reviews">Non ci sono recensioni disponibili.</p>
            <?php } ?>
        </div>
    </main>
</body>
</html>

<?php
$stmt->close();
$conn->close();
