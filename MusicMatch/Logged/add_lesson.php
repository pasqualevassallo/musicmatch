<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Gestione dei messaggi di successo e errore con la sessione
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $disciplina = $_POST['disciplina'];
    $data_ora = $_POST['data_ora'];
    $durata = $_POST['durata'];
    $prezzo = $_POST['prezzo'];

    // Controlla se tutti i campi sono riempiti
    if (empty($disciplina) || empty($data_ora) || empty($durata) || empty($prezzo)) {
        $_SESSION['error'] = "Tutti i campi devono essere riempiti.";
    } else {
        // Controlla se il prezzo è valido
        if ($prezzo < 5 || $prezzo > 100 || fmod($prezzo, 0.5) != 0) {
            $_SESSION['error'] = "Il prezzo deve essere compreso tra 5 e 100 euro e deve essere un multiplo di 0,50 euro.";
        } else {
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
                    // Ottieni l'ID dell'insegnante
                    $email = $_SESSION['email'];
                    $sql = "SELECT id_insegnante FROM Insegnante WHERE email = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        $_SESSION['error'] = "Errore nella preparazione della query per ottenere l'ID dell'insegnante: " . $conn->error;
                        header("Location: add_lesson.php");
                        exit();
                    }
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $stmt->bind_result($id_insegnante);
                    $stmt->fetch();
                    $stmt->close();

                    // Inserisci la lezione
                    $stato_lezione = "prenotabile"; // Imposta il valore per stato_prenotazione
                    $sql = "INSERT INTO Lezione (disciplina, data_ora, durata, prezzo, id_insegnante, stato_lezione) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        $_SESSION['error'] = "Errore nella preparazione della query per inserire la lezione: " . $conn->error;
                        header("Location: add_lesson.php");
                        exit();
                    }
                    $stmt->bind_param("ssidis", $disciplina, $data_ora, $durata, $prezzo, $id_insegnante, $stato_lezione);

                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Lezione aggiunta con successo!";
                    } else {
                        $_SESSION['error'] = "Errore durante l'aggiunta della lezione: " . $stmt->error;
                    }
                    $stmt->close();
                    $conn->close();
                }
            }
        }
    }

    // Ricarica la pagina per mostrare i messaggi
    header("Location: add_lesson.php");
    exit();
}

// Recupera le discipline dell'insegnante loggato
$email = $_SESSION['email'];
$sql = "SELECT id_insegnante FROM Insegnante WHERE email = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error'] = "Errore nella preparazione della query per ottenere l'ID dell'insegnante: " . $conn->error;
    header("Location: add_lesson.php");
    exit();
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id_insegnante);
$stmt->fetch();
$stmt->close();

$sql = "SELECT disciplina FROM Specializzazione WHERE id_insegnante = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['error'] = "Errore nella preparazione della query per ottenere le discipline: " . $conn->error;
    header("Location: add_lesson.php");
    exit();
}
$stmt->bind_param("i", $id_insegnante);
$stmt->execute();
$result = $stmt->get_result();
$discipline_validi = [];
while ($row = $result->fetch_assoc()) {
    $discipline_validi[] = $row['disciplina'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aggiungi Lezione</title>
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
        <h1>Aggiungi una nuova lezione</h1>

        <?php if (isset($_SESSION['success'])) { ?>
            <div class="message success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php } ?>

        <?php if (isset($_SESSION['error'])) { ?>
            <div class="message error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php } ?>

        <form action="add_lesson.php" method="POST" class="lesson-form">
            <div class="form-group">
                <label for="disciplina">Disciplina :</label>
                <select id="disciplina" name="disciplina" required>
                    <option value="" disabled selected>Scegli una disciplina</option>
                    <?php foreach ($discipline_validi as $disciplina_valida) { ?>
                        <option value="<?php echo htmlspecialchars($disciplina_valida); ?>"><?php echo htmlspecialchars($disciplina_valida); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label for="data_ora">Data e Ora :</label>
                <input type="datetime-local" id="data_ora" name="data_ora" required>
            </div>
            <div class="form-group">
                <label for="durata">Durata (minuti) :</label>
                <input type="number" id="durata" name="durata" value="30" min="30" max="120" step="5" required>
                <span class="info">Min: 30, Max: 120, Incremento: 5min</span>
            </div>
            <div class="form-group">
                <label for="prezzo">Prezzo (€) :</label>
                <input type="number" id="prezzo" name="prezzo" min="5" max="100" step="0.5" value="5" required>
                <span class="info">Min: 5, Max: 100€, Incremento: 0.50€</span>
            </div>
            <button type="submit">Aggiungi Lezione</button>
        </form>

        <a href="../Logged/teacher_home.php" class="home-link">Torna alla Home</a>
    </main>

    <footer>
        <p>© 2024 MusicMatch. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>
