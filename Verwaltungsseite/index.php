<?php
$servername = "IP_ADDRESS";     // Datenbank-Server
$username = "USER";             // Benutzername
$password = "PASSWORD";         // Passwort
$dbname = "db_prod_sonoskids";  // Datenbankname

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Startseite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
<body class="content">
    <div class="container mt-5 content">
    <div class="p-5 mb-4 bg-light rounded-3">
    <div class="container-fluid py-5 text-center">
        <h1 class="display-5 fw-bold">Willkommen zur SonosKids 6.0 Datenverwaltung</h1>
        <p class="col-md-8 fs-4 mx-auto">
            Verwalte deine SonosKids-Karten, indem du neue Einträge hinzufügst oder vorhandene Einträge bearbeitest und löschst.
        </p>

        <!-- Logo-Bereich -->
        <div class="d-flex justify-content-center align-items-center mt-4">
            <div style="width: 150px; height: 150px; border: 1px solid #ccc; overflow: hidden; border-radius: 10px;">
                <img src="SonosKids.png" alt="Projekt-Logo" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
            </div>
        </div>
    </div>
</div>

        <div class="row text-center">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Neue Daten hinzufügen</h5>
                        <p class="card-text">Füge neue SonosKids-Karten zur Datenbank hinzu.</p>
                        <a href="einfuegen.php" class="btn btn-primary">Zum Einfügen</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Daten bearbeiten und löschen</h5>
                        <p class="card-text">Sehe dir bestehende SonosKids-Karten an und bearbeite oder lösche diese.</p>
                        <a href="aendern.php" class="btn btn-warning">Zum Bearbeiten</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Daten bearbeiten und löschen</h5>
                        <p class="card-text">Sehe dir bestehende SonosKids-Karten an und bearbeite oder lösche diese.</p>
                        <a href="aendern.php" class="btn btn-warning">Zum Bearbeiten</a>
                    </div>
                </div>
            </div>
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