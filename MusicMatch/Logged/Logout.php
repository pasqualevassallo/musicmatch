<?php
session_start(); // Avvia la sessione per poter accedere alle variabili di sessione

// Distruggi tutte le variabili di sessione
$_SESSION = array();

// Se è stato utilizzato un cookie per la sessione, cancellalo
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, 
        $params["path"], $params["domain"], 
        $params["secure"], $params["httponly"]
    );
}

// Distruggi la sessione
session_destroy();

// Opzionale: eliminare eventuali cookie aggiuntivi utilizzati
if (isset($_COOKIE['your_cookie_name'])) {
    setcookie('your_cookie_name', '', time() - 3600, '/');
}

// Reindirizza alla pagina di login
header("Location: ../Authentication/LoginPage.php");
exit();
?>
