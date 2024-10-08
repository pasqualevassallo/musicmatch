<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['ruolo'])) {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $id = $_POST['id'];
    $role = $_POST['role'];

    if ($role == 'studente') {
        $sql = "DELETE FROM Studente WHERE id_studente = ?";
    } elseif ($role == 'insegnante') {
        $sql = "DELETE FROM Insegnante WHERE id_insegnante = ?";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Preparazione della query di eliminazione fallita: " . $conn->error);
    }
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "Utente eliminato con successo.";
    } else {
        echo "Errore durante l'eliminazione dell'utente: " . $conn->error;
    }
    $stmt->close();
}

$role_filter = isset($_POST['role']) ? $_POST['role'] : 'studente';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Utenti - Music Match</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/stylepersonalarea.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <header>
        <div class="header-container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <a class="navbar-brand logo" href="admin_home.php">MusicMatch</a>
                <div class="menu">
                    <a href="admin_home.php"> <i class="fas fa-home"></i>Home</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>
        </div>
    </header>

    <main class="container">
        <h1>Gestione Utenti</h1>

        <form method="post" action="view_users.php">
            <div class="form-group">
                <label for="role">Seleziona Ruolo</label>
                <select class="form-control" id="role" name="role" onchange="this.form.submit()">
                    <option value="studente" <?php echo ($role_filter == 'studente') ? 'selected' : ''; ?>>Studente</option>
                    <option value="insegnante" <?php echo ($role_filter == 'insegnante') ? 'selected' : ''; ?>>Insegnante</option>
                </select>
            </div>
        </form>

        <?php
        if ($role_filter == 'studente') {
            $sql = "SELECT id_studente, nome, cognome, email,
                       (SELECT COUNT(*) FROM Prenotazione WHERE id_studente = Studente.id_studente AND stato_prenotazione = 'da fare') AS lezioni_prenotate,
                       (SELECT COUNT(*) FROM Prenotazione WHERE id_studente = Studente.id_studente AND stato_prenotazione = 'effettuata') AS lezioni_effettuate
                FROM Studente";
        } else {
            $sql = "SELECT id_insegnante, nome, cognome, email,
                       (SELECT COUNT(*) FROM Prenotazione WHERE id_insegnante = Insegnante.id_insegnante AND stato_prenotazione = 'da fare') AS lezioni_prenotate,
                       (SELECT COUNT(*) FROM Prenotazione WHERE id_insegnante = Insegnante.id_insegnante AND stato_prenotazione = 'effettuata') AS lezioni_effettuate
                FROM Insegnante";
        }
        
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            echo '<table class="table table-striped">';
            echo '<thead><tr><th>Nome</th><th>Cognome</th><th>Email</th><th>Lezioni Prenotate</th><th>Lezioni Effettuate</th><th>Azioni</th></tr></thead>';
            echo '<tbody>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                echo '<td>' . htmlspecialchars($row['cognome']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        
                if ($role_filter == 'studente') {
                    echo '<td>' . $row['lezioni_prenotate'] . '</td>';
                    echo '<td>' . $row['lezioni_effettuate'] . '</td>';
                } else {
                    echo '<td>' . $row['lezioni_prenotate'] . '</td>';
                    echo '<td>' . $row['lezioni_effettuate'] . '</td>';
                }
        
                echo '<td>';
                echo '<form method="post" action="view_users.php" style="display:inline;">';
                echo '<input type="hidden" name="id" value="' . ($role_filter == 'studente' ? $row['id_studente'] : $row['id_insegnante']) . '">';
                echo '<input type="hidden" name="role" value="' . $role_filter . '">';
                echo '<button type="submit" name="delete" class="btn btn-danger">Elimina</button>';
                echo '</form>';
                echo '</td>';
        
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>Nessun utente trovato.</p>';
        }
        
        $conn->close();
        ?>
    </main>

    <footer class="footer bg-light mt-5">
        <div class="container text-center">
            <p>© 2024 MusicMatch. Tutti i diritti riservati.</p>
        </div>
    </footer>
</body>
</html>
