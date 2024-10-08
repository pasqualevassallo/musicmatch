<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Verifica se la richiesta è un POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica se i dati del modulo sono stati inviati
    if (!isset($_POST['Email']) || !isset($_POST['Password'])) {
        header("Location: error.php?message=Dati del modulo mancanti.");
        exit();
    }

    $email = $_POST['Email'];
    $password = $_POST['Password'];

    // Verifica che l'email sia valida
    $validDomains = ['gmail.com', 'libero.it', 'unicampania.it'];
    $emailDomain = explode('@', $email)[1];
    if (!in_array($emailDomain, $validDomains)) {
        header("Location: error.php?message=Dominio email non valido.");
        exit();
    }

    // Verifica la validità della password
    if (empty($password)) {
        header("Location: error.php?message=La password non può essere vuota.");
        exit();
    }

    // Controlla se l'utente è l'admin
    if ($email === 'admin1234@gmail.com' && $password === 'Admin1234') {
        session_start();
        $_SESSION['email'] = $email;
        $_SESSION['ruolo'] = 'admin'; // Ruolo admin
        header("Location: ../Logged/admin_home.php");
        exit();
    }

    // Prepara e esegui la query per verificare le credenziali per studente e insegnante
    $sql = "SELECT 'studente' AS ruolo, password FROM Studente WHERE email = ?
            UNION 
            SELECT 'insegnante' AS ruolo, password FROM Insegnante WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            header("Location: error.php?message=Email non trovata.");
            exit();
        }

        $stmt->bind_result($ruolo, $hashedPassword);
        $stmt->fetch();
        $stmt->close();

        // Verifica la password
        if (password_verify($password, $hashedPassword)) {
            // Inizia la sessione
            session_start();
            $_SESSION['email'] = $email;
            $_SESSION['ruolo'] = $ruolo; // Salva anche il ruolo dell'utente

            // Reindirizza l'utente alla pagina corretta
            if ($ruolo == 'studente') {
                header("Location: ../Logged/student_home.php");
            } else {
                header("Location: ../Logged/teacher_home.php");
            }
            exit();
        } else {
            header("Location: error.php?message=Password errata.");
            exit();
        }
    } else {
        header("Location: error.php?message=Errore nella preparazione della query: " . $conn->error);
        exit();
    }
} else {
    header("Location: error.php?message=Richiesta non valida.");
    exit();
}

// Chiudi la connessione al database
$conn->close();
?>
