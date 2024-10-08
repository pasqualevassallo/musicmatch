<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Controlla se è stato fornito l'ID della lezione da modificare
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../Logged/teacher_home.php");
    exit();
}

$id_lezione = $_GET['id'];  // Prende il parametro 'id' dall'URL

// Recupera i dettagli della lezione da modificare
$sql = "SELECT disciplina, data_ora, durata, prezzo FROM Lezione WHERE id_lezione = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // Se la query non può essere preparata, mostra un messaggio di errore
    $_SESSION['error'] = "Errore nella preparazione della query per ottenere i dettagli della lezione: " . $conn->error;
    header("Location: ../Logged/teacher_home.php");
    exit();
}

$stmt->bind_param("i", $id_lezione);
$stmt->execute();
$result = $stmt->get_result();
$lezione = $result->fetch_assoc();
$stmt->close();

// Se la lezione non è trovata, mostra un messaggio di errore
if (!$lezione) {
    $_SESSION['error'] = "Lezione non trovata.";
    header("Location: ../Logged/teacher_home.php");
    exit();
}

// Gestione del salvataggio della modifica
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data_ora = $_POST['data_ora'];

    // Controlla se la data è valida
    $current_date = new DateTime();
    $lesson_date = new DateTime($data_ora);
    $max_date = clone $current_date;
    $max_date->modify('+1 year');

    if ($lesson_date < $current_date) {
        $_SESSION['error'] = "Non puoi prenotare per una data passata.";
    } elseif ($lesson_date > $max_date) {
        $_SESSION['error'] = "Non puoi prenotare per una data oltre un anno da oggi.";
    } else {
        // Controlla se l'orario è valido
        $lesson_hour = (int) $lesson_date->format('H');
        if ($lesson_hour < 8 || $lesson_hour >= 21) {
            $_SESSION['error'] = "Le lezioni possono essere prenotate solo tra le 08:00 e le 21:00.";
        } else {
            // Aggiorna la lezione con la nuova data e ora
            $sql = "UPDATE Lezione SET data_ora = ? WHERE id_lezione = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                $_SESSION['error'] = "Errore nella preparazione della query per aggiornare la lezione: " . $conn->error;
                header("Location: edit_lesson.php?id=" . $id_lezione);
                exit();
            }
            $stmt->bind_param("si", $data_ora, $id_lezione);

            if ($stmt->execute()) {
                $_SESSION['success'] = "Lezione aggiornata con successo!";
            } else {
                $_SESSION['error'] = "Errore durante l'aggiornamento della lezione: " . $stmt->error;
            }
            $stmt->close();
            $conn->close();

            // Ricarica la pagina per mostrare i messaggi
            header("Location: edit_lesson.php?id=" . $id_lezione);
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Lezione</title>
    <link rel="stylesheet" href="css/styles_lessons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="profile">
                <a href="../Logged/personal_area.php"><i class="fas fa-user"></i> Area Personale</a>
                <a href="Logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <h1>Modifica lezione</h1>

        <?php if (isset($_SESSION['success'])) { ?>
            <div class="message success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php } ?>

        <?php if (isset($_SESSION['error'])) { ?>
            <div class="message error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php } ?>

        <form action="edit_lesson.php?id=<?php echo $id_lezione; ?>" method="POST" class="lesson-form">
            <div class="form-group">
                <label for="disciplina">Disciplina :</label>
                <input type="text" id="disciplina" name="disciplina" value="<?php echo htmlspecialchars($lezione['disciplina']); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="durata">Durata (minuti) :</label>
                <input type="number" id="durata" name="durata" value="<?php echo htmlspecialchars($lezione['durata']); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="prezzo">Prezzo (€) :</label>
                <input type="number" id="prezzo" name="prezzo" value="<?php echo htmlspecialchars($lezione['prezzo']); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="data_ora">Data e Ora :</label>
                <input type="datetime-local" id="data_ora" name="data_ora" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($lezione['data_ora']))); ?>" required>
            </div>
            <button type="submit">Modifica Lezione</button>
        </form>

        <a href="../Logged/teacher_home.php" class="home-link">Torna alla Home</a>
    </main>

    <footer>
        <p>© 2024 MusicMatch. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>
