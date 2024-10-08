<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

// Recupera l'ID e il nome dell'insegnante
$email = $_SESSION['email'];
$sql = "SELECT id_insegnante, nome FROM Insegnante WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($id_insegnante, $nome);
$stmt->fetch();
$stmt->close();

// Recupera le lezioni dell'insegnante loggato
$sql = "SELECT id_lezione, disciplina, data_ora, durata, prezzo 
        FROM Lezione
        WHERE id_insegnante = ? AND stato_lezione = 'prenotabile'
        ORDER BY data_ora ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_insegnante);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Insegnante</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/styles_home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="menu">
                <a href="add_lesson.php">Aggiungi Lezione</a>
                <a href="view_reservations.php">Visualizza Prenotazioni</a>
                <a href="view_reviews.php">Visualizza Recensioni</a>
            </div>

            <div class="profile">
                <a href="../Logged/personal_area.php"><i class="fas fa-user"></i> Area Personale</a>
                <a href="Logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>

        </nav>
    </header>

    <main>
        <h1>Benvenuto, <?php echo htmlspecialchars($nome); ?>! Le tue lezioni ancora disponibili :</h1>
        <div class="lesson-list">
            <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="lesson-item">
                <?php
                // Immagine di default se non esiste una specifica immagine per la disciplina
                $immagine = '../Logged/images/' . htmlspecialchars($row['disciplina']) . '.jpg';
                ?>
                <img src="<?php echo $immagine; ?>" alt="<?php echo htmlspecialchars($row['disciplina']); ?>">
                <div class="lesson-details">
                    <p><strong>Disciplina:</strong> <?php echo htmlspecialchars($row['disciplina']); ?></p>
                    <p><strong>Data e Ora:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($row['data_ora']))); ?></p>
                    <p><strong>Durata:</strong> <?php echo htmlspecialchars($row['durata']); ?> minuti</p>
                    <p><strong>Prezzo:</strong> €<?php echo htmlspecialchars($row['prezzo']); ?></p>
                    <div class="button-group">
                        <a href="edit_lesson.php?id=<?php echo htmlspecialchars($row['id_lezione']); ?>" class="button edit">Modifica Lezione</a>
                        <a href="remove_lesson.php?id=<?php echo htmlspecialchars($row['id_lezione']); ?>" class="button remove">Rimuovi Lezione</a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </main>
</body>
</html>
