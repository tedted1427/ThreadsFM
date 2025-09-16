<?php
$db_host = 'localhost';
$db_name = 'ThreadsFM';
$db_user = 'threadsfm_db';
$db_pass = 'ThR3ads!2025secure';

session_start();
$conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $threads_username = trim($_POST['threads_username'] ?? '');

    // Basisvalidatie
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Vul een geldig e-mailadres in.';
    } elseif (strlen($password) < 6) {
        $error = 'Wachtwoord moet minimaal 6 tekens zijn.';
    } else {
        // Check of e-mail al bestaat
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Dit e-mailadres is al geregistreerd.';
        } else {
            // Wachtwoord hashen
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password_hash, threads_username) VALUES (?, ?, ?)");
            $stmt->execute([$email, $password_hash, $threads_username ?: null]);
            $success = 'Account aangemaakt! Je kunt nu inloggen.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Registreren - ThreadsFM</title>
    <link href="https://fonts.googleapis.com/css?family=Libre+Franklin:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/webp" href="placeholder.webp">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <main>
        <div class="login-box">
            <h2>Account aanmaken</h2>
            <?php if ($error): ?>
                <div class="closed"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="info"><?= htmlspecialchars($success) ?></div>
                <p><a href="/home">Terug naar home</a> of <a href="/register">Inloggen</a></p>
            <?php else: ?>
                <form method="post" action="">
                    <label>E-mail: <input type="email" name="email" required></label>
                    <label>Wachtwoord: <input type="password" name="password" required></label>
                    <label>Threads username (optioneel): <input type="text" name="threads_username" autocomplete="off"></label>
                    <button type="submit">Account aanmaken</button>
                </form>
                <p>Al een account? <a href="/login">Inloggen</a></p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>