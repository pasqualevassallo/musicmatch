<?php
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Recupera l'ID della lezione e dell'insegnante dalla query string
$id_lezione = isset($_GET['id_lezione']) ? intval($_GET['id_lezione']) : 0;
$id_insegnante = isset($_GET['id_insegnante']) ? intval($_GET['id_insegnante']) : 0;
$email = $_SESSION['email'];

// Recupera l'ID dello studente
$sql = "SELECT id_studente FROM Studente WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id_studente);
$stmt->fetch();
$stmt->close();

// Controlla se esiste già una recensione per questa lezione
$sql = "SELECT COUNT(*) FROM Recensione WHERE id_studente = ? AND id_lezione = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_studente, $id_lezione);
$stmt->execute();
$stmt->bind_result($recensioni_count);
$stmt->fetch();
$stmt->close();

if ($recensioni_count > 0) {
    $message = "Hai già scritto una recensione per questa lezione.";
}

// Gestisci l'invio del modulo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $recensioni_count === 0) {
    $testo = $_POST['testo'];
    $valutazione = intval($_POST['valutazione']);

    // Verifica che la valutazione sia tra 1 e 5
    if ($valutazione < 1 || $valutazione > 5) {
        $message = "La valutazione deve essere compresa tra 1 e 5.";
    } else {
        // Inserisci la recensione nel database
        $sql = "INSERT INTO Recensione (testo, valutazione, id_studente, id_insegnante, id_lezione) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("siiii", $testo, $valutazione, $id_studente, $id_insegnante, $id_lezione);
        if ($stmt->execute()) {
            header("Location: view_completed_lessons.php"); // Redirect to completed lessons page
            exit();
        } else {
            $message = "Si è verificato un errore durante l'invio della recensione.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scrivi Recensione</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/styles_home.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
        }
        textarea {
            width: calc(100% - 2rem); /* Ridotto per il padding */
            max-width: 100%;
            padding: 1rem;
            border-radius: 4px;
            border: 1px solid #ddd;
            resize: vertical; /* Permette solo il ridimensionamento verticale */
        }
        .star-rating {
            direction: rtl;
            display: inline-block;
            font-size: 2rem;
            color: lightgray;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            cursor: pointer;
            color: lightgray;
        }
        .star-rating input:checked ~ label {
            color: gold;
        }
        .star-rating input:checked ~ input + label {
            color: lightgray;
        }
        .button-container {
            margin-top: 1rem;
        }
        .submit-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        .submit-button:hover {
            background-color: #0056b3;
        }
        .message {
            margin-bottom: 1rem;
            color: #d9534f;
        }
    </style>
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
                <a href="../Logged/personal_area.php">Area Personale</a>
                <a href="Logout.php">Logout</a>
            </div>
        </nav>
    </header>
    <main>
        <div class="container">
            <h1>Scrivi una Recensione</h1>

            <?php if (isset($message)): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="post" action="">
                <label for="testo">Descrizione:</label>
                <textarea id="testo" name="testo" placeholder="Scrivi la tua recensione qui (opzionale)"></textarea>
                
                <br><br>

                <label for="valutazione">Valutazione (da 1 a 5 stelle):</label>
                <div class="star-rating">
                    <input type="radio" id="5-stars" name="valutazione" value="5" required />
                    <label for="5-stars">&#9733;</label>
                    <input type="radio" id="4-stars" name="valutazione" value="4" />
                    <label for="4-stars">&#9733;</label>
                    <input type="radio" id="3-stars" name="valutazione" value="3" />
                    <label for="3-stars">&#9733;</label>
                    <input type="radio" id="2-stars" name="valutazione" value="2" />
                    <label for="2-stars">&#9733;</label>
                    <input type="radio" id="1-star" name="valutazione" value="1" />
                    <label for="1-star">&#9733;</label>
                </div>

                <div class="button-container">
                    <button type="submit" class="submit-button">Invia Recensione</button>
                </div>
            </form>
        </div>
    </main>
</body>
</html>

