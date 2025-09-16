<?php
$db_host = 'localhost';
$db_name = 'ThreadsFM';
$db_user = 'threadsfm_db';
$db_pass = 'ThR3ads!2025secure';

session_start();
$conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);

$stmt = $conn->prepare("SELECT value FROM settings WHERE key_name = 'voting_deadline'");
$stmt->execute();
$voting_deadline = $stmt->fetchColumn();

$now = date('Y-m-d H:i:s');
$stembus_open = ($now < $voting_deadline);

$user_email = $_SESSION['user_email'] ?? null;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>ThreadsFM - Threads Top 100 2025</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Space Mono & Montserrat -->
    <link href="https://fonts.googleapis.com/css?family=Space+Mono:700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/webp" href="placeholder.webp">
    <link rel="stylesheet" href="styles.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', Arial, sans-serif;
            background: linear-gradient(180deg, #9D17B9 0%, #E8B4F7 100%);
            min-height: 100vh;
            color: #111;
        }
        .vertical-banner {
            position: absolute;
            left: 0;
            top: 0;
            height: 100vh;
            width: 100vw;
            background: linear-gradient(185deg, #9D17B9 0%, #E8B4F7 85%);
            z-index: 0;
            overflow: hidden;
        }
        .vertical-text-arrow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 165px;
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            z-index: 2;
        }
        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            font-family: 'Space Mono', monospace;
            font-size: 2.1rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 2px;
            margin-left: 20px;
            margin-top: 70px;
            line-height: 1.05;
            text-shadow: 1px 1px 0 #8d0ea6;
            user-select: none;
        }
        .arrow-right {
            margin-top: 44px;
            margin-left: 20px;
            font-size: 2.5rem;
            color: #fff;
            text-shadow: 1px 1px 0 #8d0ea6;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .arrow-right:hover {
            transform: scale(1.1);
        }
        .container {
            position: relative;
            z-index: 3;
            margin: 180px 0 0 0;
            padding: 0 16px 16px 16px;
            max-width: 390px;
        }
        .main-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(140, 16, 140, 0.10);
            padding: 28px 22px 22px 22px;
            margin-bottom: 30px;
        }
        .main-card p {
            font-family: 'Montserrat', Arial, sans-serif;
            font-size: 1.07rem;
            margin-top: 0;
            margin-bottom: 18px;
        }
        .main-card strong {
            font-weight: 700;
        }
        .main-card .cta-btn {
            display: block;
            width: 100%;
            font-family: 'Space Mono', monospace;
            font-size: 1.12rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(90deg, #BC41D7 0%, #C66ACB 100%);
            border: none;
            border-radius: 10px;
            padding: 11px 0;
            margin-top: 8px;
            margin-bottom: 0;
            cursor: pointer;
            box-shadow: 0 2px 10px #9d17b97a;
            transition: background 0.18s;
        }
        .main-card .cta-btn:hover {
            background: linear-gradient(90deg, #9D17B9 0%, #BC41D7 100%);
        }
        .faq-section {
            margin-top: 28px;
        }
        .faq-section h2 {
            font-family: 'Space Mono', monospace;
            font-size: 1.17rem;
            font-weight: 700;
            margin-bottom: 10px;
            margin-top: 0;
        }
        .faq-list {
            font-family: 'Montserrat', Arial, sans-serif;
        }
        .faq-list .q {
            font-weight: 700;
            margin-top: 16px;
            margin-bottom: 4px;
            font-size: 1.01rem;
        }
        .faq-list .q.is-threads {
            color: #9D17B9;
        }
        .faq-list .q.is-threads-fm {
            color: #BC41D7;
        }
        .faq-list .q.is-stemmen {
            color: #C66ACB;
        }
        .faq-list .q.is-info {
            color: #8d0ea6;
        }
        .faq-list .q.is-aanpassen {
            color: #9D17B9;
        }
        .faq-list .a {
            font-weight: 400;
            margin-top: 0;
            margin-bottom: 13px;
            font-size: 1rem;
            color: #222;
        }
        footer {
            width: 100%;
            text-align: center;
            font-family: 'Space Mono', monospace;
            font-size: 0.98rem;
            color: #8d0ea6;
            margin-bottom: 11px;
            margin-top: 38px;
            letter-spacing: 0.05em;
        }
        @media (max-width: 390px) {
            .container {
                padding: 0 4px;
            }
        }
    </style>
</head>
<body>
    <div class="vertical-banner"></div>
    <div class="vertical-text-arrow">
        <div class="vertical-text">
            Stem nu op jouw favorieten<br>voor de Threads Top100
        </div>
        <a href="/vote.php" class="arrow-right" title="Naar de stembus">&#8594;</a>
    </div>
    <div class="container">
        <div class="main-card">
            <p>ThreadsFM organiseert<br>
            de Threads Top 100 2025<br>
            voor en door de community.<br><br>
            Iedereen kan gratis meedoen:<br>
            kies jouw favoriete tracks<br>
            en bepaal samen de ultieme<br>
            Threads-lijst!</p>
            <?php if ($stembus_open): ?>
                <a href="/vote.php" class="cta-btn">Naar de stembus</a>
            <?php else: ?>
                <div class="cta-btn" style="background:#ccc;color:#fff;cursor:not-allowed;">Stembus gesloten</div>
            <?php endif; ?>
        </div>
        <div class="faq-section">
            <h2>Veel gestelde vragen</h2>
            <div class="faq-list">
                <div class="q is-threads">Is dit in samenwerking met Threads?</div>
                <div class="a">Nee. Dit is een onafhankelijk initiatief van subtotedted en niet verbonden<br>aan Threads of Meta.</div>

                <div class="q is-threads-fm">Wat is ThreadsFM?</div>
                <div class="a">ThreadsFM is een gratis platform voor<br>de community om samen te stemmen<br>op de beste nummers van het jaar.</div>

                <div class="q is-stemmen">Hoe kan ik stemmen?</div>
                <div class="a">Klik op de knop <strong>Naar de stembus</strong> en maak een gratis account aan. Daarna kan je je favoriete nummers kiezen.</div>

                <div class="q is-info">Waarom vragen jullie naar mijn leeftijd,<br>locatie en muzieksmaak?</div>
                <div class="a">Gewoon uit nieuwsgierigheid! Zo kunnen wij leuke trends en inzichten delen. Het invullen van deze informatie is niet verplicht.</div>

                <div class="q is-aanpassen">Kan ik mijn stem later aanpassen?</div>
                <div class="a">Ja, zolang de stembus open is kan je je stemlijst aanpassen.</div>
            </div>
        </div>
    </div>
    <footer>
        &copy;ThreadsFM, <?= date('Y') ?> &ndash; Door subtotedted
    </footer>
</body>
</html>
