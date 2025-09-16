<?php
$db_host = 'localhost';
$db_name = 'ThreadsFM';
$db_user = 'threadsfm_db';
$db_pass = 'ThR3ads!2025secure';

session_start();
$conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basisvalidatie
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Vul een geldig e-mailadres in.';
    } else {
        // Zoek gebruiker
        $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Onjuist e-mailadres of wachtwoord.';
        } else {
            // Login gelukt
            $_SESSION['user_email'] = $email;
            $_SESSION['user_id'] = $user['id'];
            header('Location: /account');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Inloggen - ThreadsFM</title>
    <link href="https://fonts.googleapis.com/css?family=Libre+Franklin:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/webp" href="placeholder.webp">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <main>
        <div class="login-box">
            <h2>Inloggen</h2>
            <?php if ($error): ?>
                <div class="closed"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <label>E-mail: <input type="email" name="email" required></label>
                <label>Wachtwoord: <input type="password" name="password" required></label>
                <button type="submit">Inloggen</button>
            </form>
            <p>Nog geen account? <a href="/register">Account aanmaken</a></p>
        </div>
    </main>
</body>
</html>