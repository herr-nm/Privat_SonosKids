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

// Löschen eines Datensatzes
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM tbl_karte WHERE kartePK = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("s", $delete_id);
    $stmt->execute();
    $stmt->close();
    echo "<div class='alert alert-success text-center'>Datensatz erfolgreich gelöscht.</div>";
}

// Laden eines Datensatzes zum Bearbeiten
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $select_sql = "SELECT * FROM tbl_karte WHERE kartePK = ?";
    $stmt = $conn->prepare($select_sql);
    $stmt->bind_param("s", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
    $stmt->close();
}

// Aktualisieren eines Datensatzes
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kartePK'])) {
    $kartePK = strtoupper($_POST['kartePK']);
    $interpret = $_POST['interpret'];
    $titel = $_POST['titel'];
    $typFK = $_POST['typFK'];
    $linksuffix = $_POST['spotify_link'];

    $update_sql = "UPDATE tbl_karte SET interpret=?, titel=?, typFK=?, linksuffix=? WHERE kartePK=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssiss", $interpret, $titel, $typFK, $linksuffix, $kartePK);
    $stmt->execute();
    $stmt->close();
    echo "<div class='alert alert-success text-center'>Datensatz erfolgreich aktualisiert.</div>";
    $edit_data = null; // Reset edit mode
}

// Abrufen aller Datensätze
$sql = "SELECT k.kartePK, k.interpret, k.titel, t.bezeichnung AS typ, k.linksuffix
        FROM tbl_karte k
        JOIN tbl_typ t ON k.typFK = t.typPK
        WHERE interpret != 'Steuerungstag'
        ORDER BY interpret, titel";
$result = $conn->query($sql);

// Dropdown-Optionen abrufen
$sql_typ = "SELECT typPK, bezeichnung FROM tbl_typ";
$result_typ = $conn->query($sql_typ);

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datensätze anzeigen, bearbeiten und löschen</title>
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
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Gespeicherte Spotify-Daten</h1>

    <?php if ($edit_data): ?>
        <h2 class="mb-4">Datensatz bearbeiten</h2>
        <form method="POST">
            <div class="mb-3">
                <label for="kartePK" class="form-label">Karten-ID</label>
                <input type="text" class="form-control" name="kartePK" value="<?php echo htmlspecialchars($edit_data['kartePK']); ?>" readonly>
            </div>
            <div class="mb-3">
                <label for="interpret" class="form-label">Interpret</label>
                <input type="text" class="form-control" name="interpret" value="<?php echo htmlspecialchars($edit_data['interpret']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="titel" class="form-label">Titel</label>
                <input type="text" class="form-control" name="titel" value="<?php echo htmlspecialchars($edit_data['titel']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="spotify_link" class="form-label">Link-Suffix</label>
                <input type="text" class="form-control" name="spotify_link" value="<?php echo htmlspecialchars($edit_data['linksuffix']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="typFK" class="form-label">Typ</label>
                <select class="form-select" name="typFK">
                    <?php while ($row = $result_typ->fetch_assoc()) {
                        $selected = ($edit_data['typFK'] == $row['typPK']) ? "selected" : "";
                        echo "<option value='" . $row['typPK'] . "' $selected>" . $row['bezeichnung'] . "</option>";
                    } ?>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Speichern</button>
            <a href="?" class="btn btn-secondary">Abbrechen</a>
        </form>
    <?php else: ?>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Karten-ID</th>
                    <th>Interpret</th>
                    <th>Titel</th>
                    <th>Linksuffix</th>
                    <th>Typ</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['kartePK']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['interpret']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['titel']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['linksuffix']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['typ']) . "</td>";
                        echo "<td>"
                            . "<a href='?edit_id=" . urlencode($row['kartePK']) . "' class='btn btn-warning btn-sm'>Bearbeiten</a> "
                            . "<a href='?delete_id=" . urlencode($row['kartePK']) . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Wirklich löschen?\");'>Löschen</a>"
                            . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>Keine Daten gefunden.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="text-left">
    <br>
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
