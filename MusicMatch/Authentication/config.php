<?php
$servername = "localhost"; // Cambia se il tuo server ha un hostname diverso
$username = "root"; // Inserisci il tuo username del database
$password = ""; // Inserisci la tua password del database
$dbname = "MusicMatch"; // Nome del database

// Crea connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controlla connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
?>
