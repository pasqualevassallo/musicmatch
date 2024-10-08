<?php
session_start();

// Abilita la visualizzazione degli errori
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verifica se l'utente è autenticato
if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Recupera l'email dalla sessione
$email = $_SESSION['email'];

// Recupera l'id della lezione dalla query string
$id_lezione = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Inizia una transazione
$conn->begin_transaction();

try {
    // Verifica se la lezione è prenotabile
    $sql = "SELECT stato_lezione FROM Lezione WHERE id_lezione = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Errore nella preparazione della query: " . $conn->error);
    }
    $stmt->bind_param("i", $id_lezione);
    $stmt->execute();
    $stmt->bind_result($stato_lezione);
    $stmt->fetch();
    $stmt->close();

    if ($stato_lezione === 'prenotabile') {
        // Aggiorna lo stato della lezione a "eliminata"
        $sql = "UPDATE Lezione SET stato_lezione = 'eliminata' WHERE id_lezione = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Errore nella preparazione della query: " . $conn->error);
        }
        $stmt->bind_param("i", $id_lezione);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "Lezione eliminata con successo.";

            // Recupera i dettagli della lezione eliminata
            $sql = "SELECT disciplina, data_ora, durata, prezzo, Insegnante.nome AS nome_insegnante, Insegnante.cognome AS cognome_insegnante 
                    FROM Lezione 
                    JOIN Insegnante ON Lezione.id_insegnante = Insegnante.id_insegnante 
                    WHERE Lezione.id_lezione = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Errore nella preparazione della query: " . $conn->error);
            }
            $stmt->bind_param("i", $id_lezione);
            $stmt->execute();
            $stmt->bind_result($disciplina, $data_ora, $durata, $prezzo, $nome_insegnante, $cognome_insegnante);
            $stmt->fetch();
            $stmt->close();
        } else {
            $message = "Errore durante l'eliminazione della lezione.";
        }
    } else {
        $message = "La lezione non è in stato prenotabile.";
    }

    // Commit della transazione
    $conn->commit();

} catch (Exception $e) {
    // Rollback della transazione in caso di errore
    $conn->rollback();
    $message = "Errore durante l'eliminazione della lezione: " . $e->getMessage();
}

// Chiudi la connessione
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminazione Lezione</title>
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
            <h1>Eliminazione Lezione</h1>
            <div class="lesson-details">
                <?php if (isset($message)): ?>
                    <p><?php echo htmlspecialchars($message); ?></p>
                <?php endif; ?>
                <?php if (isset($disciplina)): ?>
                    <p><strong>ID Lezione:</strong> <?php echo htmlspecialchars($id_lezione); ?></p>
                    <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($disciplina); ?></p>
                    <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($data_ora))); ?></p>
                    <p><strong>Durata:</strong> <?php echo htmlspecialchars($durata); ?> minuti</p>
                    <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($prezzo); ?></p>
                    <p><strong>Insegnante:</strong> <?php echo htmlspecialchars($nome_insegnante . ' ' . $cognome_insegnante); ?></p>
                <?php endif; ?>
            </div>
            <a href="teacher_home.php" class="home-button">Torna alla Homepage</a>
        </div>
    </main>
</body>
</html>
