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

// Suchlogik
$search_query = "";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = strtolower($_GET['search']);
    $sql = "SELECT k.kartePK, k.interpret, k.titel, t.bezeichnung AS typ, k.linksuffix
            FROM tbl_karte k
            JOIN tbl_typ t ON k.typFK = t.typPK
            WHERE LOWER(k.kartePK) LIKE ? 
               OR LOWER(k.interpret) LIKE ?
               OR LOWER(k.titel) LIKE ?
               OR LOWER(t.bezeichnung) LIKE ?
            ORDER BY k.interpret, k.titel";
    $stmt = $conn->prepare($sql);
    $search_pattern = "%$search_query%";
    $stmt->bind_param("ssss", $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Standardabfrage ohne Suchfilter
    $sql = "SELECT k.kartePK, k.interpret, k.titel, t.bezeichnung AS typ, k.linksuffix
            FROM tbl_karte k
            JOIN tbl_typ t ON k.typFK = t.typPK
            ORDER BY k.interpret, k.titel";
    $result = $conn->query($sql);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inhalte suchen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html { overflow-y: scroll; }
        body { margin: 0; padding-bottom: 80px; }
        footer {
            background-color: #212529;
            color: white;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4 text-center">Inhalte suchen</h1>

    <!-- Suchfeld -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Suche nach Karten-ID, Interpret, Titel oder Typ" 
                   value="<?php echo htmlspecialchars($search_query); ?>">
            <button class="btn btn-primary" type="submit">Suchen</button>
            <a href="?" class="btn btn-secondary">Zurücksetzen</a>
        </div>
    </form>

    <!-- Ergebnis-Tabelle -->
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Karten-ID</th>
                <th>Interpret</th>
                <th>Titel</th>
                <th>Linksuffix</th>
                <th>Typ</th>
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
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>Keine passenden Inhalte gefunden.</td></tr>";
            }
            ?>
        </tbody>
    </table>
    <br>
    <div class="text-left">
        <a href="index.php" class="btn btn-secondary">zurück zur Startseite</a>
    </div>
</div>

<!-- Footer -->
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>