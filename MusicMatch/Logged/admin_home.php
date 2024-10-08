<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'admin1234@gmail.com') {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php';

$teacherId = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : 0;

// Funzione per ottenere le statistiche dell'insegnante
function getTeacherStats($conn, $teacherId) {
    $sql = "SELECT 
        (SELECT COUNT(*) FROM Lezione WHERE stato_lezione = 'prenotabile' AND id_insegnante = ?) AS lezioni_disponibili,
        (SELECT COUNT(*) FROM Prenotazione WHERE stato_prenotazione = 'da fare' AND id_insegnante = ?) AS prenotazioni_da_fare,
        (SELECT COUNT(*) FROM Prenotazione WHERE stato_prenotazione = 'effettuata' AND id_insegnante = ?) AS prenotazioni_effettuate";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $teacherId, $teacherId, $teacherId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

if ($teacherId > 0) {
    header('Content-Type: application/json');
    echo json_encode(getTeacherStats($conn, $teacherId));
    exit();
}

// Recupera le statistiche generali
$sqlLezioniPrenotazioni = "SELECT 
    (SELECT COUNT(*) FROM Lezione WHERE stato_lezione = 'prenotabile') AS lezioni_disponibili,
    (SELECT COUNT(*) FROM Prenotazione WHERE stato_prenotazione = 'da fare') AS prenotazioni_da_fare,
    (SELECT COUNT(*) FROM Prenotazione WHERE stato_prenotazione = 'effettuata') AS prenotazioni_effettuate,
    (SELECT COUNT(*) FROM Insegnante) AS num_insegnanti,
    (SELECT COUNT(*) FROM Studente) AS num_studenti";
$resultLezioniPrenotazioni = $conn->query($sqlLezioniPrenotazioni);
if (!$resultLezioniPrenotazioni) {
    die("Errore nella query: " . $conn->error);
}
$stats = $resultLezioniPrenotazioni->fetch_assoc();

// Recupera gli insegnanti per il menu a tendina
$sqlInsegnanti = "SELECT id_insegnante, nome, cognome FROM Insegnante";
$resultInsegnanti = $conn->query($sqlInsegnanti);
if (!$resultInsegnanti) {
    die("Errore nella query: " . $conn->error);
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home - Music Match</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/styles_home.css">
    <link rel="stylesheet" href="css/styles_admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .navbar {
            padding: 0em;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu a, .profile a {
            margin-right: 15px;
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 20px;
        }

        h1, h2, h3 {
            font-weight: 700;
            color: #333;
        }

        .stats-container {
            margin: 20px auto;
            text-align: center;
        }

        #teacherSelect {
            width: 250px;
            padding: 10px;
            margin-top: 15px;
            border-radius: 5px;
            border: 2px solid #007bff;
        }

        .dashboard {
            display: flex;
            justify-content: space-around; /* Distribuisce equamente gli elementi */
            flex-wrap: nowrap; /* Impedisce il wrapping su più righe */
            margin: 20px auto;
            max-width: 1550px; /* Puoi regolare la larghezza massima se necessario */
        }
        
        .dashboard-item {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 18%;
            margin: 10px;
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Aggiunge una transizione graduale */
        }

        .dashboard-item:hover {
            transform: translateY(-10px); /* Solleva l'elemento */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); /* Aumenta l'ombra per un effetto di profondità */
        }


        .chart-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 900px;
        }

        footer {
            background-color: #f1f1f1;
            text-align: center;
            padding: 10px;
            color: #888;
            position: relative;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">MusicMatch</div>
            <div class="menu">
                <a href="admin_home.php">Home</a>
                <a href="view_users.php">Visualizza Lista Utenti</a>
                <a href="add_discipline.php">Aggiungi Disciplina</a>
            </div>
            <div class="profile">
                <a href="Logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <section class="stats-container">
            <h1>Benvenuto, Admin! Ecco le statistiche della tua web application :</h1>
            <select id="teacherSelect">
                <option value="">-- Seleziona un insegnante --</option>
                <?php
                if ($resultInsegnanti->num_rows > 0) {
                    while ($row = $resultInsegnanti->fetch_assoc()) {
                        echo "<option value='" . $row['id_insegnante'] . "'>" . htmlspecialchars($row['nome']) . " " . htmlspecialchars($row['cognome']) . "</option>";
                    }
                } else {
                    echo "<option value=''>Nessun insegnante trovato</option>";
                }
                ?>
            </select>
        </section>

        <section>
    <div class="dashboard">
        <div class="dashboard-item">
            <h3>Lezioni Disponibili</h3>
            <p id="lezioniDisponibili"><?php echo htmlspecialchars($stats['lezioni_disponibili']); ?></p>
        </div>
        <div class="dashboard-item">
            <h3>Lezioni Prenotate</h3>
            <p id="prenotazioniDaFare"><?php echo htmlspecialchars($stats['prenotazioni_da_fare']); ?></p>
        </div>
        <div class="dashboard-item">
            <h3>Lezioni Effettuate</h3>
            <p id="prenotazioniEffettuate"><?php echo htmlspecialchars($stats['prenotazioni_effettuate']); ?></p>
        </div>
        <div class="dashboard-item">
            <h3>Insegnanti Registrati</h3>
            <p id="numInsegnanti"><?php echo htmlspecialchars($stats['num_insegnanti']); ?></p>
        </div>
        <div class="dashboard-item">
            <h3>Studenti Registrati</h3>
            <p id="numStudenti"><?php echo htmlspecialchars($stats['num_studenti']); ?></p>
        </div>
    </div>
</section>


        <section>
            <h2>Grafico delle Lezioni e Prenotazioni</h2>
            <div class="chart-container">
                <canvas id="statsChart"></canvas>
            </div>
        </section>
    </main>

    <footer>
        <p>© 2024 MusicMatch. Tutti i diritti riservati.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('statsChart').getContext('2d');
    let chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Lezioni Prenotabili', 'Lezioni Prenotate', 'Lezioni Effettuate'],
            datasets: [{
                label: 'Numero Lezioni',
                data: [
                    <?php echo $stats['lezioni_disponibili']; ?>,
                    <?php echo $stats['prenotazioni_da_fare']; ?>,
                    <?php echo $stats['prenotazioni_effettuate']; ?>
                ],
                backgroundColor: [
                    'rgba(135, 206, 250, 0.5)',
                    'rgba(255, 223, 186, 0.5)',
                    'rgba(255, 182, 193, 0.5)'
                ],
                borderColor: [
                    'rgba(135, 206, 250, 1)',
                    'rgba(255, 223, 186, 1)',
                    'rgba(255, 182, 193, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    document.getElementById('teacherSelect').addEventListener('change', function () {
        const teacherId = this.value;
        if (teacherId) {
            fetch(`admin_home.php?teacher_id=${teacherId}`)
                .then(response => response.json())
                .then(data => {
                    // Aggiorna il grafico con i dati dell'insegnante
                    chart.data.datasets[0].data = [
                        data.lezioni_disponibili,
                        data.prenotazioni_da_fare,
                        data.prenotazioni_effettuate
                    ];
                    chart.update();
                })
                .catch(error => console.error('Errore:', error));
        } else {
            // Aggiorna il grafico con i dati generali se non è selezionato nessun insegnante
            chart.data.datasets[0].data = [
                <?php echo $stats['lezioni_disponibili']; ?>,
                <?php echo $stats['prenotazioni_da_fare']; ?>,
                <?php echo $stats['prenotazioni_effettuate']; ?>
            ];
            chart.update();
        }
    });
});

    </script>
</body>
</html>
