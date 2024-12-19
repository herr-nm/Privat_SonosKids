<?php
$servername = "IP_ADDRESS";     // Datenbank-Server
$username = "USER";             // Benutzername
$password = "PASSWORD";         // Passwort
$dbname = "db_prod_sonoskids";  // Datenbankname

// Verbindung zur Datenbank
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Dropdown-Optionen aus der Datenbank abrufen
$sql = "SELECT typPK, bezeichnung FROM tbl_typ";
$result = $conn->query($sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kartePK = strtoupper($_POST['kartePK']); // Kleinbuchstaben in Großbuchstaben umwandeln
    $interpret = $_POST['interpret'];
    $titel = $_POST['titel'];
    $typFK = $_POST['typFK'];
    $spotify_link = trim($_POST['spotify_link']);

    // Typ aus der Datenbank abrufen
    $stmt_typ = $conn->prepare("SELECT bezeichnung FROM tbl_typ WHERE typPK = ?");
    $stmt_typ->bind_param("i", $typFK);
    $stmt_typ->execute();
    $result_typ = $stmt_typ->get_result();
    $typ_bezeichnung = strtolower($result_typ->fetch_assoc()['bezeichnung']);

    // Verarbeite den Spotify-Link basierend auf Typ
    $linksuffix = null;

    if ($typ_bezeichnung == "steuerungstag") {
        // Steuerungstags werden direkt übernommen
        $linksuffix = $spotify_link;
    } elseif (!empty($spotify_link) && strpos($spotify_link, "spotify") !== false) {
        // Spotify-Link analysieren
        $parsed_url = parse_url($spotify_link);
        $spotify_id = basename($parsed_url['path']);

        if ($typ_bezeichnung == "hörspiel" || $typ_bezeichnung == "album") {
            $linksuffix = "spotify/now/spotify:album:" . $spotify_id;
        } elseif ($typ_bezeichnung == "playlist") {
            $linksuffix = "spotify/now/spotify:playlist:" . $spotify_id;
        } elseif ($typ_bezeichnung == "lied") {
            $linksuffix = "spotify/now/spotify:track:" . $spotify_id;
        } else {
            echo "<div class='alert alert-danger mt-3'>Ungültiger Typ für Spotify-Link.</div>";
            exit();
        }
    } else {
        echo "<div class='alert alert-danger mt-3'>Ungültiger Spotify-Link.</div>";
        exit();
    }

    // SQL-Abfrage zum Einfügen des neuen Eintrags
    $stmt = $conn->prepare("INSERT INTO tbl_karte (kartePK, typFK, interpret, titel, linksuffix) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $kartePK, $typFK, $interpret, $titel, $linksuffix);

    if ($stmt->execute()) {
        echo "<div class='alert alert-success mt-3'>Daten erfolgreich eingefügt.</div>";
    } else {
        echo "<div class='alert alert-danger mt-3'>Fehler: " . $stmt->error . "</div>";
    }

    $stmt->close();
    $stmt_typ->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify-Daten Eintragen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function toUpperCaseInput(input) {
            input.value = input.value.toUpperCase();
        }
    </script>
    <style>
        html {
            overflow-y: scroll; /* Scrollbar immer anzeigen */
        }
        body {
            margin: 0;
            padding-bottom: 120px; /* Platz für den Footer */
        }
        footer {
            background-color: #212529; /* Dunkler Hintergrund */
            color: white;
            padding: 15px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            height: 80px; /* Definierte Höhe */
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Spotify-Daten eintragen</h1>
    <form method="POST">
        <div class="mb-3">
            <label for="kartePK" class="form-label">Karten-ID</label>
            <input type="text" class="form-control" id="kartePK" name="kartePK" oninput="toUpperCaseInput(this)" required>
        </div>
        <div class="mb-3">
            <label for="interpret" class="form-label">Interpret</label>
            <input type="text" class="form-control" id="interpret" name="interpret" required>
        </div>
        <div class="mb-3">
            <label for="titel" class="form-label">Titel</label>
            <input type="text" class="form-control" id="titel" name="titel" required>
        </div>
        <div class="mb-3">
            <label for="spotify_link" class="form-label">Spotify-Link</label>
            <input type="url" class="form-control" id="spotify_link" name="spotify_link" placeholder="https://open.spotify.com/..." required>
        </div>
        <div class="mb-3">
            <label for="typFK" class="form-label">Typ</label>
            <select class="form-select" id="typFK" name="typFK">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['typPK'] . "'>" . $row['bezeichnung'] . "</option>";
                    }
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Eintragen</button>
    </form>
    <br>
    <div class="text-left">
        <a href="index.php" class="btn btn-secondary">zurück zur Startseite</a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<footer>
    <div class="container d-flex justify-content-between align-items-center">
    <div style="font-size: 0.8rem;">
        &copy; 2024 <a href="https://herr-nm.de" class="text-white text-decoration-none">herr-nm.de</a>
        </div>
        <div>
            <img src="logo.png" alt="Logo" style="width: 30px; height: 30px;">
        </div>
        <div style="font-size: 0.8rem;">
            Lizenziert unter der 
            <a href="https://creativecommons.org/licenses/by-nc-sa/4.0/" class="text-white text-decoration-none" target="_blank">CC-BY-NC-SA 4.0</a>
        </div>
    </div>
</footer>
</body>
</html>
