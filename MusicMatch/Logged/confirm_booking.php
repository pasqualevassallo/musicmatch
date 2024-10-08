<?php
session_start();

// Verifica se l'utente è autenticato
if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Recupera l'email e l'id_studente dalla sessione
$email = $_SESSION['email'];

$sql = "SELECT id_studente FROM Studente WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id_studente);
$stmt->fetch();
$stmt->close();

// Recupera i dati dalla richiesta POST
$id_lezione = intval($_POST['id_lezione']);
$id_insegnante = intval($_POST['id_insegnante']);
$data_prenotazione = date('Y-m-d');

// Inserisci la prenotazione nel database
$sql = "INSERT INTO Prenotazione (data_prenotazione, id_studente, id_insegnante, id_lezione) 
        VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("siii", $data_prenotazione, $id_studente, $id_insegnante, $id_lezione);
$stmt->execute();

// Aggiorna lo stato della lezione nel database
$sql = "UPDATE Lezione SET stato_lezione = 'prenotata' WHERE id_lezione = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_lezione);
$stmt->execute();
$stmt->close();

// Recupera i dettagli della lezione prenotata
$sql = "SELECT l.disciplina, l.data_ora, l.durata, l.prezzo, l.id_insegnante, i.nome AS nome_insegnante, i.cognome AS cognome_insegnante, i.email AS email_insegnante 
        FROM Lezione l 
        JOIN Insegnante i ON l.id_insegnante = i.id_insegnante 
        WHERE l.id_lezione = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_lezione);
$stmt->execute();
$stmt->bind_result($disciplina, $data_ora, $durata, $prezzo, $id_insegnante, $nome_insegnante, $cognome_insegnante, $email_insegnante);
$stmt->fetch();
$stmt->close();

// Calcola la media delle valutazioni dell'insegnante
$sql = "SELECT AVG(valutazione) as media_valutazioni FROM Recensione WHERE id_insegnante = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_insegnante);
$stmt->execute();
$stmt->bind_result($media_valutazioni);
$stmt->fetch();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conferma Prenotazione</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
        }

        header {
            background: linear-gradient(135deg, #c6ddff, #ffcac5);
            padding: 15px 20px;
            color: #2c3e50;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            left: 0;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .navbar .logo {
            font-size: 24px;
            font-weight: 600;
        }

        .navbar .profile {
            display: flex;
            gap: 15px;
        }

        .navbar .profile a {
            color: #2c3e50;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
        }

        .navbar .profile a:hover {
            text-decoration: underline;
        }

        main {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding-top: 70px; /* Spazio per la barra di navigazione */
        }

        .lesson-container {
            background-color: #ebfbc5;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }

        h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .lesson-details p {
            font-size: 18px;
            margin: 10px 0;
        }

        .lesson-details p strong {
            font-weight: bold;
            color: #555;
        }

        .home-button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #3498db;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .home-button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="profile">
                <a href="../Logged/personal_area.php">Area Personale</a>
                <a href="Logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="lesson-container">
            <h1>Lezione Prenotata con Successo</h1>
            <div class="lesson-details">
                <p><strong>ID Lezione:</strong> <?php echo htmlspecialchars($id_lezione); ?></p>
                <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($disciplina); ?></p>
                <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($data_ora))); ?></p>
                <p><strong>Durata:</strong> <?php echo htmlspecialchars($durata); ?> minuti</p>
                <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($prezzo); ?></p>
                <p><strong>Insegnante:</strong> <?php echo htmlspecialchars($nome_insegnante . ' ' . $cognome_insegnante); ?></p>
                <p><strong>Email Insegnante:</strong> <?php echo htmlspecialchars($email_insegnante); ?></p>
                <p><strong>Media Valutazioni Insegnante:</strong> <?php echo htmlspecialchars(number_format($media_valutazioni, 2)); ?> / 5</p>
            </div>
            <a href="student_home.php" class="home-button">Torna alla Homepage</a>
        </div>
    </main>
</body>
</html>