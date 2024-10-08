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

// Recupera il ruolo e l'email dell'utente
$email = $_SESSION['email'];
$ruolo = $_SESSION['ruolo'];

if ($ruolo == 'studente') {
    // Recupera i dati per lo studente
    $sql = "SELECT nome, cognome, email, livello_abilita FROM Studente WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($nome, $cognome, $email, $livello_abilita);
    $stmt->fetch();
    $stmt->close();
} else {
    // Recupera i dati per l'insegnante
    $sql = "SELECT id_insegnante, nome, cognome, email FROM Insegnante WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id_insegnante, $nome, $cognome, $email);
    $stmt->fetch();
    $stmt->close();

    // Recupera le certificazioni dell'insegnante con nome e ente
    $sql = "SELECT nome_certificazione, ente_fornitore FROM Certificazione WHERE id_insegnante = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->bind_param("i", $id_insegnante);
    $stmt->execute();
    $stmt->bind_result($nome_certificazione, $ente_fornitore);
    $certificazioni = [];
    while ($stmt->fetch()) {
        $certificazioni[] = [
            'nome' => $nome_certificazione,
            'ente' => $ente_fornitore
        ];
    }
    $stmt->close();

    // Recupera le specializzazioni dell'insegnante
    $sql = "SELECT disciplina FROM Specializzazione WHERE id_insegnante = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->bind_param("i", $id_insegnante);
    $stmt->execute();
    $stmt->bind_result($nome_specializzazione);
    $specializzazioni = [];
    while ($stmt->fetch()) {
        $specializzazioni[] = $nome_specializzazione;
    }
    $stmt->close();

    // Recupera tutte le discipline disponibili
    $sql = "SELECT id_disciplina, nome FROM Disciplina";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->execute();
    $stmt->bind_result($id_disciplina, $nome_disciplina);
    $discipline = [];
    while ($stmt->fetch()) {
        $discipline[] = [
            'id' => $id_disciplina,
            'nome' => $nome_disciplina
        ];
    }
    $stmt->close();

    // Filtra le discipline non specializzate
    $discipline_disponibili = array_filter($discipline, function($disciplina) use ($specializzazioni) {
        return !in_array($disciplina['nome'], $specializzazioni);
    });

    // Calcola la valutazione media delle recensioni ricevute dall'insegnante
    $sql = "SELECT AVG(valutazione) AS media_valutazione FROM Recensione WHERE id_insegnante = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Preparazione della query fallita: " . $conn->error);
    }
    $stmt->bind_param("i", $id_insegnante);
    $stmt->execute();
    $stmt->bind_result($media_valutazione);
    $stmt->fetch();
    $stmt->close();

    // Verifica se media_valutazione è null e assegna un valore di default
    if ($media_valutazione !== null) {
        $media_valutazione = round($media_valutazione, 2); // Arrotonda a 2 cifre decimali
    } else {
        $media_valutazione = "N/A"; // O un altro valore come 0.0 se preferisci
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($ruolo == 'studente') {
        $livello_abilita = $_POST['livello_abilita'];
        
        $update_sql = "UPDATE Studente SET livello_abilita = ? WHERE email = ?";
        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            die("Preparazione della query di aggiornamento fallita: " . $conn->error);
        }
        $stmt->bind_param("ss", $livello_abilita, $email);

        if ($stmt->execute()) {
            $_SESSION['email'] = $email;
            header("Location: personal_area.php?message=Profilo aggiornato con successo.");
            exit();
        } else {
            echo "Errore durante l'aggiornamento del profilo: " . $conn->error;
        }
        $stmt->close();
    } elseif ($ruolo == 'insegnante') {
        $ente_fornitore = $_POST['ente_fornitore'];
        $nome_certificazione = $_POST['nome_certificazione'];
        $id_disciplina = $_POST['disciplina'];
        
        if (!empty($nome_certificazione)) {
            $insert_sql = "INSERT INTO Certificazione (nome_certificazione, ente_fornitore, id_insegnante) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            if (!$stmt) {
                die("Preparazione della query di inserimento fallita: " . $conn->error);
            }
            $stmt->bind_param("ssi", $nome_certificazione, $ente_fornitore, $id_insegnante);

            if ($stmt->execute()) {
                header("Location: personal_area.php?message=Certificazione aggiunta con successo.");
                exit();
            } else {
                echo "Errore durante l'inserimento della certificazione: " . $conn->error;
            }
            $stmt->close();
        } else {
            echo "Per favore, inserisci il nome della certificazione.";
        }
        
        if (!empty($id_disciplina)) {
            $insert_specializzazione_sql = "INSERT INTO Specializzazione (id_disciplina, disciplina, id_insegnante) 
                                            SELECT id_disciplina, nome, ? FROM Disciplina WHERE id_disciplina = ?";
            $stmt = $conn->prepare($insert_specializzazione_sql);
            if (!$stmt) {
                die("Preparazione della query di inserimento fallita: " . $conn->error);
            }
            $stmt->bind_param("ii", $id_insegnante, $id_disciplina);

            if ($stmt->execute()) {
                header("Location: personal_area.php?message=Specializzazione aggiunta con successo.");
                exit();
            } else {
                echo "Errore durante l'inserimento della specializzazione: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Area Personale - Music Match</title>
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
                <a class="navbar-brand logo" href="<?php echo ($ruolo == 'studente') ? 'student_home.php' : 'teacher_home.php'; ?>">MusicMatch</a>
                <div class="menu">
                    <a href="<?php echo ($ruolo == 'studente') ? 'student_home.php' : 'teacher_home.php'; ?>">
                        <i class="fas fa-home"></i> Home
                     </a>
                     <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <h1>Area Personale</h1>

        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_GET['message']); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="personal_area.php">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="cognome">Cognome</label>
                <input type="text" class="form-control" id="cognome" name="cognome" value="<?php echo htmlspecialchars($cognome); ?>" readonly>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
            </div>
            
            <?php if ($ruolo == 'studente'): ?>
                <div class="form-group">
                    <label for="livello_abilita">Livello Abilità</label>
                    <select class="form-control" id="livello_abilita" name="livello_abilita" required>
                        <option value="principiante" <?php echo ($livello_abilita == 'principiante') ? 'selected' : ''; ?>>Principiante</option>
                        <option value="intermedio" <?php echo ($livello_abilita == 'intermedio') ? 'selected' : ''; ?>>Intermedio</option>
                        <option value="avanzato" <?php echo ($livello_abilita == 'avanzato') ? 'selected' : ''; ?>>Avanzato</option>
                    </select>
                </div>
            <?php elseif ($ruolo == 'insegnante'): ?>
                <div class="form-group">
                    <label for="media_valutazione">Valutazione Media</label>
                    <input type="text" class="form-control" id="media_valutazione" name="media_valutazione" value="<?php echo htmlspecialchars($media_valutazione); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="certificazioni">Certificazioni</label>
                    <?php if (!empty($certificazioni)): ?>
                        <ul>
                            <?php foreach ($certificazioni as $certificazione): ?>
                                <li>
                                    <?php echo htmlspecialchars($certificazione['nome']); ?> - 
                                    <?php echo htmlspecialchars($certificazione['ente']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Nessuna certificazione aggiunta.</p>
                    <?php endif; ?>
                </div>
                <div class="form-row">
                    <div class="col">
                        <div class="form-group">
                            <label for="nome_certificazione">Nome Certificazione</label>
                            <input type="text" class="form-control" id="nome_certificazione" name="nome_certificazione" placeholder="Inserisci il nome della certificazione">
                        </div>
                    </div>
                    <div class="col">
                        <div class="form-group">
                            <label for="ente_fornitore">Ente Fornitore</label>
                            <input type="text" class="form-control" id="ente_fornitore" name="ente_fornitore" placeholder="Inserisci l'ente fornitore (opzionale)">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="disciplina">Aggiungi Specializzazione</label>
                    <select class="form-control" id="disciplina" name="disciplina">
                        <option value="">Seleziona una disciplina</option>
                        <?php foreach ($discipline_disponibili as $disciplina): ?>
                            <option value="<?php echo htmlspecialchars($disciplina['id']); ?>">
                                <?php echo htmlspecialchars($disciplina['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Specializzazioni</label>
                    <?php if (!empty($specializzazioni)): ?>
                        <ul>
                            <?php foreach ($specializzazioni as $specializzazione): ?>
                                <li><?php echo htmlspecialchars($specializzazione); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>Nessuna specializzazione aggiunta.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary">Aggiorna Profilo</button>
        </form>
    </main>
</body>
</html>

