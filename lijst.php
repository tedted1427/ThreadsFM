<?php
// Toon fouten (voor debuggen, alleen in ontwikkeling)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// DATABASEGEGEVENS INVOEREN
$db_host = 'localhost';
$db_name = 'ThreadsFM';
$db_user = 'threadsfm_db';
$db_pass = 'ThR3ads!2025secure';



try {
    $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Fout bij verbinden met database: " . $e->getMessage());
}

// Haal de tussenstand op
$stmt = $conn->prepare("
    SELECT track_name, artist_name, COUNT(*) AS stemmen
    FROM votes
    GROUP BY track_name, artist_name
    ORDER BY stemmen DESC, track_name ASC
");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Tussenstand - ThreadsFM</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: sans-serif;
            background: #f4f4f4;
            padding: 2rem;
        }
        table {
            width: 100%;
            max-width: 700px;
            margin: auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #eee;
        }
        h1 {
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <h1>Tussenstand Stemmen</h1>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Track</th>
                <th>Artiest</th>
                <th>Stemmen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= htmlspecialchars($row['track_name']) ?></td>
                    <td><?= htmlspecialchars($row['artist_name']) ?></td>
                    <td><?= $row['stemmen'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
