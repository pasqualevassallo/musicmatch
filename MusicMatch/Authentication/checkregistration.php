<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica che tutti i dati necessari siano stati inviati
    if (!isset($_POST['Nome']) || !isset($_POST['Cognome']) || !isset($_POST['Email']) || !isset($_POST['Password']) || !isset($_POST['Ruolo'])) {
        header("Location: error.php?message=Dati del modulo mancanti.");
        exit();
    }

    $nome = $_POST['Nome'];
    $cognome = $_POST['Cognome'];
    $email = $_POST['Email'];
    $password = $_POST['Password'];
    $ruolo = $_POST['Ruolo'];
    $livello = isset($_POST['Livello']) ? $_POST['Livello'] : null;
    $discipline = isset($_POST['Disciplina']) ? $_POST['Disciplina'] : [];

    // Verifica la validità dell'email
    $validDomains = ['gmail.com', 'libero.it', 'unicampania.it'];
    $emailDomain = explode('@', $email)[1];
    if (!in_array($emailDomain, $validDomains)) {
        header("Location: error.php?message=Dominio email non valido.");
        exit();
    }

    // Verifica la validità della password
    $passwordRegex = '/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/';
    if (!preg_match($passwordRegex, $password)) {
        header("Location: error.php?message=La password deve essere di almeno 8 caratteri, contenere almeno una lettera maiuscola e un numero.");
        exit();
    }

    // Verifica se l'email è già in uso
    $sql = "SELECT email FROM Studente WHERE email = ? UNION SELECT email FROM Insegnante WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            header("Location: error.php?message=L'email è già utilizzata. Per favore, usa un'email diversa.");
            exit();
        }

        $stmt->close();
    } else {
        header("Location: error.php?message=Errore nella preparazione della query: " . $conn->error);
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Aggiungi l'utente al database
    if ($ruolo === 'studente') {
        $sql = "INSERT INTO Studente (nome, cognome, email, password, livello_abilita) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $nome, $cognome, $email, $password_hash, $livello);
            $stmt->execute();
        } else {
            header("Location: error.php?message=Errore nella preparazione della query per studente: " . $conn->error);
            exit();
        }
    } elseif ($ruolo === 'insegnante') {
        $sql = "INSERT INTO Insegnante (nome, cognome, email, password) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $nome, $cognome, $email, $password_hash);
            if ($stmt->execute()) {
                $id_insegnante = $stmt->insert_id; // Ottieni l'ID dell'insegnante appena inserito

                // Rimuovi le specializzazioni precedenti per l'insegnante
                $sql = "DELETE FROM Specializzazione WHERE id_insegnante = ?";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("i", $id_insegnante);
                    $stmt->execute();
                } else {
                    header("Location: error.php?message=Errore nella preparazione della query per rimozione specializzazioni: " . $conn->error);
                    exit();
                }

                // Preleva gli ID delle discipline
                $placeholders = implode(',', array_fill(0, count($discipline), '?'));
                $sql = "SELECT id_disciplina, nome FROM Disciplina WHERE id_disciplina IN ($placeholders)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param(str_repeat('i', count($discipline)), ...$discipline);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $disciplineData = [];
                    while ($row = $result->fetch_assoc()) {
                        $disciplineData[$row['id_disciplina']] = $row['nome'];
                    }
                } else {
                    header("Location: error.php?message=Errore nella preparazione della query per il recupero delle discipline: " . $conn->error);
                    exit();
                }

                // Inserire ogni disciplina selezionata nella tabella Specializzazione
                $sql = "INSERT INTO Specializzazione (id_disciplina, id_insegnante, disciplina) VALUES (?, ?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    foreach ($discipline as $disciplina_id) {
                        $disciplina_nome = $disciplineData[$disciplina_id] ?? null;
                        if ($disciplina_nome !== null) {
                            $stmt->bind_param("iis", $disciplina_id, $id_insegnante, $disciplina_nome);
                            $stmt->execute();
                        }
                    }
                } else {
                    header("Location: error.php?message=Errore nella preparazione della query per inserimento delle specializzazioni: " . $conn->error);
                    exit();
                }
            } else {
                header("Location: error.php?message=Errore nell'inserimento dell'insegnante: " . $stmt->error);
                exit();
            }
        } else {
            header("Location: error.php?message=Errore nella preparazione della query per insegnante: " . $conn->error);
            exit();
        }
    } else {
        header("Location: error.php?message=Ruolo non valido.");
        exit();
    }

    // Chiudere la dichiarazione
    $stmt->close();

    // Reindirizza alla pagina di successo
    header("Location: RegistrationSuccess.php");
    exit();
} else {
    header("Location: error.php?message=Richiesta non valida.");
    exit();
}

// Chiudere la connessione
$conn->close();
?>
