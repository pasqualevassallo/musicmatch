<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['email']) || $_SESSION['email'] !== 'admin1234@gmail.com') {
    header("Location: ../Authentication/LoginPage.php");
    exit();
}

require_once '../Authentication/config.php'; // Assicurati che contenga la connessione al database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $discipline_name = $_POST['discipline_name'];
    $image = $_FILES['discipline_image'];

    // Validazione
    if (empty($discipline_name)) {
        $error = "Il nome della disciplina è obbligatorio.";
    } elseif ($image['error'] != 0) {
        $error = "Errore nel caricamento dell'immagine.";
    } elseif ($image['type'] != 'image/jpeg') {
        $error = "Il formato dell'immagine deve essere JPEG.";
    } else {
        // Verifica se la disciplina esiste già nel database
        $query = "SELECT * FROM Disciplina WHERE nome = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $discipline_name);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "La disciplina esiste già nel database.";
                $stmt->close();
            } else {
                // Sanitizzazione del nome della disciplina per l'immagine
                $sanitized_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $discipline_name);
                $target_dir = "../Logged/images/";
                $target_file = $target_dir . $sanitized_name . '.jpg';

                // Salvataggio dell'immagine
                if (move_uploaded_file($image['tmp_name'], $target_file)) {
                    // Inserimento della nuova disciplina nel database
                    $query = "INSERT INTO Disciplina (nome) VALUES (?)";
                    if ($stmt = $conn->prepare($query)) {
                        $stmt->bind_param("s", $discipline_name);
                        if ($stmt->execute()) {
                            $success = "Disciplina aggiunta con successo.";
                        } else {
                            $error = "Errore durante l'inserimento della disciplina: " . $conn->error;
                        }
                        $stmt->close(); // Chiudi l'oggetto mysqli_stmt qui
                    } else {
                        $error = "Errore nella preparazione della query.";
                    }
                } else {
                    $error = "Errore nel salvataggio dell'immagine.";
                }
            }
        } else {
            $error = "Errore nella preparazione della query.";
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
    <title>Aggiungi Disciplina</title>
    <link rel="stylesheet" href="css/styles.css"> 
    <link rel="stylesheet" href="css/styles_home.css">
    <link rel="stylesheet" href="css/styles_admin.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #007bff;
            color: #fff;
            padding: 15px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .menu a {
            color: #fff;
            margin: 0 10px;
            text-decoration: none;
            font-size: 16px;
            transition: color 0.3s;
        }
        .menu a:hover {
            color: #d4d4d4;
        }
        .profile a {
            color: #fff;
            text-decoration: none;
            font-size: 16px;
        }
        main {
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        .form-label {
            font-weight: 600;
            color: #555;
        }
        .form-control {
            border-radius: 4px;
            border: 1px solid #ccc;
            padding: 10px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
            margin-bottom: 15px;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            color: #fff;
            padding: 12px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 10px 0;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #333;
            font-size: 16px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        footer {
            text-align: center;
            padding: 15px;
            background-color: #f1f1f1;
            border-top: 1px solid #e1e1e1;
            position: absolute;
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
                <a href="admin_home.php"> <i class="fas fa-home"></i>Home</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>
    </header>

    <main>
        <div class="container">
            <h2>Aggiungi Nuova Disciplina</h2>

            <?php if (isset($error)) : ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)) : ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="add_discipline.php" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="discipline_name" class="form-label">Nome della Disciplina</label>
                    <input type="text" class="form-control" id="discipline_name" name="discipline_name" required>
                </div>

                <div class="mb-3">
                    <label for="discipline_image" class="form-label">Immagine della Disciplina (JPEG)</label>
                    <input type="file" class="form-control" id="discipline_image" name="discipline_image" accept="image/jpeg" required>
                </div>

                <button type="submit" class="btn btn-primary">Aggiungi Disciplina</button>
            </form>
        </div>
    </main>

    <footer>
        <p>© 2024 MusicMatch. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>

