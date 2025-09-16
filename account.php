<?php
$db_host = 'localhost';
$db_name = 'ThreadsFM';
$db_user = 'threadsfm_db';
$db_pass = 'ThR3ads!2025secure';

session_start();
$conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);

// Check of gebruiker is ingelogd
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Haal huidige user info op
$stmt = $conn->prepare("SELECT email, threads_username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Check of gebruiker al gestemd heeft
$stmt = $conn->prepare("SELECT COUNT(*) FROM votes WHERE user_id = ?");
$stmt->execute([$user_id]);
$has_voted = $stmt->fetchColumn() > 0;

// Verwerken van updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Threads username wijzigen
    if (isset($_POST['update_threads'])) {
        $threads_username = trim($_POST['threads_username'] ?? '');
        $stmt = $conn->prepare("UPDATE users SET threads_username = ? WHERE id = ?");
        $stmt->execute([$threads_username ?: null, $user_id]);
        $success = 'Threads gebruikersnaam bijgewerkt!';
        $user['threads_username'] = $threads_username;
    }
    // E-mail wijzigen
    if (isset($_POST['update_email'])) {
        $new_email = trim($_POST['new_email'] ?? '');
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Vul een geldig e-mailadres in.';
        } else {
            // Check of e-mail al bestaat
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$new_email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Dit e-mailadres is al in gebruik.';
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$new_email, $user_id]);
                $success = 'E-mailadres bijgewerkt!';
                $user['email'] = $new_email;
                $_SESSION['user_email'] = $new_email;
            }
        }
    }
    // Wachtwoord wijzigen
    if (isset($_POST['update_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        if (strlen($new_password) < 6) {
            $error = 'Wachtwoord moet minimaal 6 tekens zijn.';
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$password_hash, $user_id]);
            $success = 'Wachtwoord bijgewerkt!';
        }
    }
    // Account verwijderen
    if (isset($_POST['delete_account'])) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        session_destroy();
        header('Location: /home');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Accountbeheer - ThreadsFM</title>
    <link href="https://fonts.googleapis.com/css?family=Libre+Franklin:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" type="image/webp" href="placeholder.webp">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <main>
        <div class="login-box">
            <h2>Accountbeheer</h2>
            <?php if ($error): ?>
                <div class="closed"><?= htmlspecialchars($error) ?></div>
            <?php elseif ($success): ?>
                <div class="info"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <div class="stembus-banner">
                <?php if (!$has_voted): ?>
                    <p class="stembus-text">Stem nu voor de Threads Top100 van 2025!</p>
                    <a href="/vote"><button class="stembus-btn">Ga naar de stembus</button></a>
                <?php else: ?>
                    <p class="stembus-text">Je hebt al gestemd! Je kunt je stemlijst nog aanpassen.</p>
                    <a href="/vote"><button class="stembus-btn">Stemlijst aanpassen</button></a>
                <?php endif; ?>
            </div>
            <hr>
            <form method="post" action="">
                <label>Threads gebruikersnaam (optioneel):
                    <input type="text" name="threads_username" value="<?= htmlspecialchars($user['threads_username'] ?? '') ?>" autocomplete="off">
                </label>
                <button type="submit" name="update_threads">Opslaan</button>
            </form>
            <hr>
            <form method="post" action="">
                <label>Huidig e-mailadres:
                    <input type="email" name="new_email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </label>
                <button type="submit" name="update_email">E-mailadres wijzigen</button>
            </form>
            <hr>
            <form method="post" action="">
                <label>Nieuw wachtwoord:
                    <input type="password" name="new_password" required>
                </label>
                <button type="submit" name="update_password">Wachtwoord wijzigen</button>
            </form>
            <hr>
            <form method="post" action="" onsubmit="return confirm('Weet je zeker dat je je account wilt verwijderen? Dit kan niet ongedaan worden gemaakt!');">
                <button type="submit" name="delete_account" style="background:#a00;color:#fff;">Account verwijderen</button>
            </form>
            <hr>
            <a href="/home">Terug naar home</a> | <a href="/logout">Uitloggen</a>
        </div>
    </main>
</body>
</html>