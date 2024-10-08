<!DOCTYPE html>
<html lang="it">
<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    // Connessione al database
    require_once 'config.php';

    // Recupera le discipline dal database
    $disciplinaQuery = "SELECT id_disciplina, nome FROM Disciplina";
    $disciplinaResult = $conn->query($disciplinaQuery);

    // Controlla se ci sono discipline
    $disciplineOptions = "";
    if ($disciplinaResult->num_rows > 0) {
        while ($row = $disciplinaResult->fetch_assoc()) {
            $id_disciplina = htmlspecialchars($row['id_disciplina']);
            $nome_disciplina = htmlspecialchars($row['nome']);
            $disciplineOptions .= "<option value=\"$id_disciplina\">$nome_disciplina</option>";
        }
    } else {
        $disciplineOptions = "<option value=\"\">Nessuna disciplina disponibile</option>";
    }
    $conn->close();
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrati - Music Match</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function redirectToLogin() {
            window.location.href = 'LoginPage.php';
        }

        function toggleRoleFields() {
            const isStudent = document.getElementById('studente').checked;
            document.getElementById('student-fields').classList.toggle('hidden', !isStudent);
            document.getElementById('teacher-fields').classList.toggle('hidden', isStudent);
            document.getElementById('role-specific-fields').classList.remove('hidden');
        }

        function validateForm() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Validate email domain
            const validDomains = ['gmail.com', 'libero.it', 'unicampania.it'];
            const emailDomain = email.split('@')[1];
            if (!validDomains.includes(emailDomain)) {
                alert('Il dominio dell\'email deve essere uno dei seguenti: gmail.com, libero.it, unicampania.it');
                return false;
            }

            // Validate password
            const passwordRegex = /^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/;
            if (!passwordRegex.test(password)) {
                alert('La password deve essere di almeno 8 caratteri, contenere almeno una lettera maiuscola e un numero.');
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
            <p>Unisciti alla nostra community e condividi il tuo potenziale musicale. Impara, cresci e brilla con noi!</p>
        </div>
    </header>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg">
                    <div class="card-header">
                        <h4 class="mb-0">Registrati</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="checkregistration.php" onsubmit="return validateForm()">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="nome">Nome</label>
                                    <input type="text" class="form-control" id="nome" name="Nome" maxlength="50" placeholder="Nome" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="cognome">Cognome</label>
                                    <input type="text" class="form-control" id="cognome" name="Cognome" maxlength="50" placeholder="Cognome" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="Email" maxlength="100" placeholder="Email" required>
                                <small id="emailHelp" class="form-text text-muted">Domini validi : gmail.com, libero.it, unicampania.it</small>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="Password" placeholder="Password (min 8 caratteri, almeno una maiuscola e un numero)" required>
                                <small id="passwordHelp" class="form-text text-muted">La password deve essere di almeno 8 caratteri, contenere almeno una lettera maiuscola e un numero.</small>
                            </div>
                            <div class="form-group">
                                <label for="ruolo">Ruolo</label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="Ruolo" id="studente" value="studente" onchange="toggleRoleFields()" required>
                                        <label class="form-check-label" for="studente">Studente</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="Ruolo" id="insegnante" value="insegnante" onchange="toggleRoleFields()" required>
                                        <label class="form-check-label" for="insegnante">Insegnante</label>
                                    </div>
                                </div>
                            </div>

                            <div id="role-specific-fields" class="hidden">
                                <!-- Studente -->
                                <div id="student-fields" class="form-group hidden">
                                    <label for="livello">Livello di abilità</label>
                                    <select class="form-control" id="livello" name="Livello">
                                        <option value="principiante">Principiante</option>
                                        <option value="intermedio">Intermedio</option>
                                        <option value="avanzato">Avanzato</option>
                                    </select>
                                </div>

                                <!-- Insegnante -->
                                <div id="teacher-fields" class="form-group hidden">
                                    <label for="disciplina">Discipline Musicali</label>
                                    <select class="form-control" id="disciplina" name="Disciplina[]" multiple>
                                        <?php echo $disciplineOptions; ?>
                                    </select>
                                    <small class="form-text text-muted">Tieni premuto Ctrl (o Cmd su Mac) per selezionare più discipline.</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Registrati</button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>Hai già un account? <a href="LoginPage.php">Accedi qui</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center">
        <p>&copy; 2024 Music Match. Tutti i diritti riservati.</p>
    </footer>
</body>
</html>
