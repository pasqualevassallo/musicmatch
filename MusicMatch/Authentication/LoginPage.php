<!DOCTYPE html>
<html lang="it">
<?php
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Music Match</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function redirectToRegister() {
            window.location.href = 'RegisterPage.php';
        }

        function validateLogin() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Validate email domain (same as registration)
            const validDomains = ['gmail.com', 'libero.it', 'unicampania.it'];
            const emailDomain = email.split('@')[1];
            if (emailDomain && !validDomains.includes(emailDomain)) {
                alert('Il dominio dell\'email deve essere uno dei seguenti: gmail.com, libero.it, unicampania.it');
                return false;
            }

            // Ensure password is not empty
            if (password.trim() === '') {
                alert('La password non può essere vuota.');
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <header>
        <div class="header-content">
            <h1><i class="fas fa-music"></i> Music Match</h1>
            <p>Accedi al tuo account e inizia il tuo viaggio musicale con noi. Per esplorare lezioni, risorse e molto altro!</p>
        </div>
    </header>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-header">
                        <h4 class="mb-0">Accedi</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="checklogin.php" onsubmit="return validateLogin()">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="Email" placeholder="Email" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="Password" placeholder="Password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Accedi</button>
                        </form>
                        <div class="text-center mt-3">
                            <p>Non hai un account? <a href="javascript:void(0);" onclick="redirectToRegister()">Registrati</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>