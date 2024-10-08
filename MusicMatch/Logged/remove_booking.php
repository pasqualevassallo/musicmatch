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

// Recupera i dati dalla query string
$id_prenotazione = isset($_GET['id_prenotazione']) ? intval($_GET['id_prenotazione']) : 0;
$id_lezione = isset($_GET['id_lezione']) ? intval($_GET['id_lezione']) : 0;

// Inizia una transazione
$conn->begin_transaction();

try {
    // Elimina la prenotazione dal database
    $sql = "DELETE FROM Prenotazione WHERE id_prenotazione = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_prenotazione);
    $stmt->execute();

    // Aggiorna lo stato della lezione a "prenotabile"
    $sql = "UPDATE Lezione SET stato_lezione = 'prenotabile' WHERE id_lezione = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_lezione);
    $stmt->execute();

    // Commit della transazione
    $conn->commit();
    $stmt->close();

    $message = "Prenotazione annullata con successo.";

    // Recupera i dettagli della lezione annullata
    $sql = "SELECT disciplina, data_ora, durata, prezzo, Insegnante.nome AS nome_insegnante, Insegnante.cognome AS cognome_insegnante 
            FROM Lezione 
            JOIN Insegnante ON Lezione.id_insegnante = Insegnante.id_insegnante 
            WHERE Lezione.id_lezione = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_lezione);
    $stmt->execute();
    $stmt->bind_result($disciplina, $data_ora, $durata, $prezzo, $nome_insegnante, $cognome_insegnante);
    $stmt->fetch();
    $stmt->close();

} catch (Exception $e) {
    // Rollback della transazione in caso di errore
    $conn->rollback();
    $message = "Errore durante l'annullamento della prenotazione: " . $e->getMessage();
}

// Chiudi la connessione
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annullamento Prenotazione</title>
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
            background-color: #fbd2c5;
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
            <h1>Prenotazione Annullata</h1>
            <div class="lesson-details">
                <?php if ($message): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>
                <?php if (isset($disciplina)): ?>
                    <p><strong>ID Prenotazione:</strong> <?php echo htmlspecialchars($id_prenotazione); ?></p>
                    <p><strong>ID Lezione:</strong> <?php echo htmlspecialchars($id_lezione); ?></p>
                    <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($disciplina); ?></p>
                    <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($data_ora))); ?></p>
                    <p><strong>Durata:</strong> <?php echo htmlspecialchars($durata); ?> minuti</p>
                    <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($prezzo); ?></p>
                    <p><strong>Insegnante:</strong> <?php echo htmlspecialchars($nome_insegnante . ' ' . $cognome_insegnante); ?></p>
                <?php endif; ?>
            </div>
            <a href="student_home.php" class="home-button">Torna alla Homepage</a>
        </div>
    </main>
</body>
</html>
