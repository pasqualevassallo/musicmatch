<?php
// error.php
$error_message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Errore sconosciuto.';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Errore - Music Match</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <header>
        <div class="header-content">
            <h1><i class="fas fa-music"></i> Music Match</h1>
            <p>Si è verificato un errore durante questa sessione.</p>
        </div>
    </header>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-header">
                        <h4 class="mb-0">Errore</h4>
                    </div>
                    <div class="card-body text-center">
                        <p><?php echo $error_message; ?></p>
                        <p><a href="javascript:history.back()" class="back-link">Torna indietro</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
