<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$db_host = 'localhost';
$db_name = 'ThreadsFM';
$db_user = 'threadsfm_db';
$db_pass = 'ThR3ads!2025secure';

try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Databaseverbinding mislukt: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// AJAX: zoek tracks
if (isset($_GET['q'])) {
    $q = trim($_GET['q']);
    $stmt = $conn->prepare("SELECT id, track_name, artist_name FROM tracks WHERE track_name LIKE ? OR artist_name LIKE ? LIMIT 10");
    $stmt->execute(["%$q%", "%$q%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($results);
    exit;
}

// AJAX: verwijder track uit stemlijst
if (isset($_POST['remove_vote'])) {
    $track_name = $_POST['track_name'] ?? '';
    $artist_name = $_POST['artist_name'] ?? '';
    $stmt = $conn->prepare("DELETE FROM votes WHERE user_id = ? AND track_name = ? AND artist_name = ?");
    $stmt->execute([$user_id, $track_name, $artist_name]);
    exit('OK');
}

// Stem toevoegen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_track'])) {
    $track_name = trim($_POST['track_name'] ?? '');
    $artist_name = trim($_POST['artist_name'] ?? '');
    if ($track_name && $artist_name) {
        $stmt = $conn->prepare("SELECT id FROM tracks WHERE track_name = ? AND artist_name = ?");
        $stmt->execute([$track_name, $artist_name]);
        $track = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$track) {
            $stmt = $conn->prepare("INSERT INTO tracks (track_name, artist_name) VALUES (?, ?)");
            $stmt->execute([$track_name, $artist_name]);
            $track_id = $conn->lastInsertId();
        } else {
            $track_id = $track['id'];
        }

        $stmt = $conn->prepare("SELECT id FROM votes WHERE user_id = ? AND track_id = ?");
        $stmt->execute([$user_id, $track_id]);
        if (!$stmt->fetch()) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM votes WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $count = $stmt->fetchColumn();
            if ($count < 15) {
                $stmt = $conn->prepare("INSERT INTO votes (user_id, track_id, track_name, artist_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $track_id, $track_name, $artist_name]);
                $success = 'Nummer toegevoegd aan je stemlijst!';
            } else {
                $error = 'Je mag maximaal 15 stemmen uitbrengen.';
            }
        } else {
            $error = 'Je hebt al op dit nummer gestemd.';
        }
    } else {
        $error = 'Vul zowel titel als artiest in.';
    }
}

// Stemlijst ophalen
$stmt = $conn->prepare("SELECT track_name, artist_name FROM votes WHERE user_id = ? ORDER BY voted_at ASC");
$stmt->execute([$user_id]);
$votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Opslaan van stemmen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_votes'])) {
    if (count($votes) < 5) {
        $error = 'Je moet minimaal 5 nummers op je stemlijst hebben!';
    } else {
        header('Location: /dank');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Stembus - ThreadsFM</title>
    <link href="https://fonts.googleapis.com/css?family=Libre+Franklin:400,700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    body {
        min-height: 100vh;
        margin: 0;
        font-family: 'Libre Franklin', Arial, sans-serif;
        background: linear-gradient(30deg, #7c3aed 0%, #fff 60%, #fff 100%);
    }
    .centered {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
        padding-top: 40px;
    }
    .vote-box {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 6px 32px rgba(124,58,237,0.10), 0 1.5px 6px rgba(124,58,237,0.08);
        padding: 32px 28px 28px 28px;
        max-width: 420px;
        width: 100%;
        margin-bottom: 40px;
    }
    h2 {
        margin-top: 0;
        font-weight: 700;
        color: #7c3aed;
    }
    .autocomplete-suggestions {
        background:#fff;
        border:1px solid #ccc;
        max-height:180px;
        overflow-y:auto;
        position:absolute;
        z-index:10;
        width:100%;
        left:0;
        top:100%;
        box-sizing: border-box;
    }
    .autocomplete-suggestion {
        padding:8px;
        cursor:pointer;
        border-bottom: 1px solid #eee;
    }
    .autocomplete-suggestion:last-child {
        border-bottom: none;
    }
    .autocomplete-suggestion strong {
        font-weight:bold;
        display:block;
        margin-bottom:2px;
    }
    .autocomplete-suggestion:hover {
        background:#f0e6ff;
    }
    .closed, .info {
        margin-bottom: 18px;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 1em;
    }
    .closed { background: #fde8e8; color: #b91c1c; }
    .info { background: #e9e7fd; color: #7c3aed; }
    label { display:block; margin-top:14px; font-weight:500; }
    input[type="text"] {
        width:100%;
        padding:8px;
        border-radius:7px;
        border:1px solid #ccc;
        margin-top:4px;
        font-size:1em;
        box-sizing: border-box;
    }
    button {
        margin-top:18px;
        background:#7c3aed;
        color:#fff;
        border:none;
        border-radius:7px;
        padding:10px 22px;
        font-size:1em;
        font-weight:600;
        cursor:pointer;
        box-shadow: 0 2px 8px rgba(124,58,237,0.08);
        transition: background 0.2s;
    }
    button:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    .stemlijst-box {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(124,58,237,0.10);
        padding: 18px 18px 12px 18px;
        margin-top: 24px;
        margin-bottom: 8px;
    }
    .stemlijst-box h3 {
        margin-top: 0;
        margin-bottom: 12px;
        color: #7c3aed;
        font-size: 1.12em;
    }
    .stemlijst-box ol {
        padding-left: 0;
        margin: 0;
        list-style: none;
    }
    .stemlijst-box li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 1px solid #f3f0ff;
    }
    .stemlijst-box li:last-child {
        border-bottom: none;
    }
    .track-info {
        flex: 1;
    }
    .track-title {
        font-weight: bold;
        font-size: 1.07em;
        color: #3b2f6b;
    }
    .track-artist {
        font-weight: normal;
        color: #6d6d6d;
        font-size: 0.98em;
    }
    .remove-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0 0 0 10px;
        display: flex;
        align-items: center;
    }
    .remove-btn svg {
        width: 18px;
        height: 18px;
        fill: #b91c1c;
        transition: fill 0.2s;
    }
    .remove-btn:hover svg {
        fill: #7c3aed;
    }
    .manual-box {
        background: #f6f3ff;
        border: 1.5px solid #7c3aed;
        border-radius: 12px;
        padding: 18px 16px 12px 16px;
        margin-top: 12px;
        box-shadow: 0 2px 8px rgba(124,58,237,0.07);
        display: none;
    }
    .manual-box label {
        margin-top: 8px;
    }
    .manual-actions {
        margin-top: 14px;
        display: flex;
        gap: 10px;
    }
    .manual-actions button {
        margin-top: 0;
        padding: 8px 18px;
    }
    .nog-nodig {
        margin: 10px 0 0 0;
        color: #7c3aed;
        font-weight: 500;
        font-size: 1.04em;
    }
    </style>
</head>
<body>
<div class="centered">
    <div class="vote-box">
        <h2>Stemlijst samenstellen</h2>
        <?php if ($error): ?>
            <div class="closed"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="info"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="" autocomplete="off" style="position:relative;" id="add-track-form">
            <label>Zoek nummer:
                <input type="text" name="search_track" id="search_track" autocomplete="off">
            </label>
            <div id="suggestions" class="autocomplete-suggestions"></div>
        </form>
        <div id="manual-box" class="manual-box">
            <form method="post" action="" autocomplete="off" id="manual-form">
                <label>Titel:
                    <input type="text" name="track_name" id="track_name_manual">
                </label>
                <label>Artiest:
                    <input type="text" name="artist_name" id="artist_name_manual">
                </label>
                <div class="manual-actions">
                    <button type="submit" name="add_track" id="manual-add-btn" disabled>Voeg toe</button>
                    <button type="button" id="manual-cancel-btn">Annuleer</button>
                </div>
            </form>
        </div>
        <p style="margin-top:10px;">Minimaal 5, maximaal 15 stemmen. Geen dubbele stemmen.</p>
        <div class="stemlijst-box" id="stemlijst-box">
            <h3>Jouw stemlijst:</h3>
            <ol id="stemlijst">
                <?php foreach ($votes as $vote): ?>
                    <li>
                        <span class="track-info">
                            <span class="track-title"><?= htmlspecialchars($vote['track_name']) ?></span><br>
                            <span class="track-artist"><?= htmlspecialchars($vote['artist_name']) ?></span>
                        </span>
                        <button class="remove-btn" data-track="<?= htmlspecialchars($vote['track_name']) ?>" data-artist="<?= htmlspecialchars($vote['artist_name']) ?>" title="Verwijder">
                            <svg viewBox="0 0 20 20"><path d="M6.225 6.225a.75.75 0 0 1 1.06 0L10 8.94l2.715-2.715a.75.75 0 1 1 1.06 1.06L11.06 10l2.715 2.715a.75.75 0 1 1-1.06 1.06L10 11.06l-2.715 2.715a.75.75 0 1 1-1.06-1.06L8.94 10 6.225 7.285a.75.75 0 0 1 0-1.06z"/></svg>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ol>
            <div class="nog-nodig" id="nog-nodig"></div>
        </div>
        <form method="post" action="">
            <button type="submit" name="save_votes" id="save-btn" <?= count($votes) < 5 ? 'disabled' : '' ?>>Stemlijst opslaan</button>
        </form>
        <a href="/account" style="display:block; margin-top:18px; color:#7c3aed;">Terug naar account</a>
    </div>
</div>
<script>
const searchInput = document.getElementById('search_track');
const suggestions = document.getElementById('suggestions');
const manualBox = document.getElementById('manual-box');
const manualForm = document.getElementById('manual-form');
const trackNameManual = document.getElementById('track_name_manual');
const artistNameManual = document.getElementById('artist_name_manual');
const manualAddBtn = document.getElementById('manual-add-btn');
const manualCancelBtn = document.getElementById('manual-cancel-btn');
const stemlijst = document.getElementById('stemlijst');
const nogNodig = document.getElementById('nog-nodig');
const saveBtn = document.getElementById('save-btn');

function updateNogNodig() {
    const count = stemlijst.querySelectorAll('li').length;
    if (count < 5) {
        nogNodig.textContent = `Nog ${5-count} nodig`;
        saveBtn.disabled = true;
    } else {
        nogNodig.textContent = '';
        saveBtn.disabled = false;
    }
}
updateNogNodig();

searchInput.addEventListener('input', function() {
    const q = this.value;
    suggestions.innerHTML = '';
    manualBox.style.display = 'none';
    if (q.length < 2) return;
    fetch('?q=' + encodeURIComponent(q))
        .then(res => res.json())
        .then(data => {
            suggestions.innerHTML = '';
            manualBox.style.display = 'none';
            if (data.length > 0) {
                data.forEach((item, idx) => {
                    const div = document.createElement('div');
                    div.className = 'autocomplete-suggestion';
                    div.innerHTML = `<strong>${item.track_name}</strong>${item.artist_name}`;
                    div.onclick = function() {
                        trackNameManual.value = item.track_name;
                        artistNameManual.value = item.artist_name;
                        manualBox.style.display = 'block';
                        suggestions.innerHTML = '';
                    };
                    suggestions.appendChild(div);
                });
            } else {
                suggestions.innerHTML = `<div class="autocomplete-suggestion">Geen resultaten gevonden voor "<strong>${q}</strong>"<br>Staat het nummer er niet tussen? <button id="manual-show-btn" style="background:none;border:none;color:#7c3aed;cursor:pointer;font-weight:600;">Voeg 'm zelf toe!</button></div>`;
                document.getElementById('manual-show-btn').onclick = function() {
                    trackNameManual.value = q;
                    artistNameManual.value = '';
                    manualBox.style.display = 'block';
                    suggestions.innerHTML = '';
                };
            }
        });
});

// Annuleer handmatig toevoegen
manualCancelBtn.addEventListener('click', function() {
    manualBox.style.display = 'none';
    trackNameManual.value = '';
    artistNameManual.value = '';
});

// Enable "Voeg toe" knop alleen als beide velden zijn ingevuld
function checkManualFields() {
    manualAddBtn.disabled = !(trackNameManual.value.trim() && artistNameManual.value.trim());
}
trackNameManual.addEventListener('input', checkManualFields);
artistNameManual.addEventListener('input', checkManualFields);

// Verwijder nummer uit stemlijst via AJAX
stemlijst.addEventListener('click', function(e) {
    if (e.target.closest('.remove-btn')) {
        const btn = e.target.closest('.remove-btn');
        const track = btn.getAttribute('data-track');
        const artist = btn.getAttribute('data-artist');
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `remove_vote=1&track_name=${encodeURIComponent(track)}&artist_name=${encodeURIComponent(artist)}`
        }).then(() => {
            btn.parentElement.remove();
            updateNogNodig();
        });
    }
});

// Sluit suggesties als je ergens anders klikt
document.addEventListener('click', function(e) {
    if (!e.target.closest('#search_track') && !e.target.closest('#suggestions')) {
        suggestions.innerHTML = '';
    }
});
</script>
