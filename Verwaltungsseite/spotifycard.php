<?php
// Fehlerausgabe aktivieren (für Entwicklung)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Spotify API Credentials
$client_id = 'ID'; // Ersetze durch deinen Client ID
$client_secret = 'SECRET'; // Ersetze durch deinen Client Secret

// Variablen initialisieren
$title = '';
$artist = '';
$cover_url = '';
$type = '';

function getSpotifyData($url, $access_token) {
    $headers = [
        'Authorization: Bearer ' . $access_token
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('cURL Fehler (Datenabruf): ' . curl_error($ch));
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        die("Fehler bei der Datenabfrage. HTTP-Statuscode: $http_code. Antwort: $response");
    }

    curl_close($ch);

    return json_decode($response, true);
}

// Funktion zur Authentifizierung und Abruf des Tokens
function getSpotifyAccessToken($client_id, $client_secret) {
    $url = 'https://accounts.spotify.com/api/token';
    $headers = [
        'Authorization: Basic ' . base64_encode("$client_id:$client_secret"),
        'Content-Type: application/x-www-form-urlencoded'
    ];
    $data = [
        'grant_type' => 'client_credentials'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Hier wird das Format angepasst

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('cURL Fehler: ' . curl_error($ch));
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code !== 200) {
        die("Fehler bei der Tokenanfrage. HTTP-Statuscode: $http_code. Antwort: $response");
    }

    curl_close($ch);

    $response_data = json_decode($response, true);
    return $response_data['access_token'] ?? '';
}

// Verarbeitung der Formulareingabe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $spotify_url = $_POST['spotify_url'] ?? '';
    $content_type = $_POST['content_type'] ?? '';

    if (empty($spotify_url) || empty($content_type)) {
        die("Fehler: Bitte alle Formularfelder ausfüllen.");
    }

    // Extrahiere die ID aus dem Spotify-Link
    preg_match('/(?:album|playlist)\/([a-zA-Z0-9]+)/', $spotify_url, $matches);
    $id = $matches[1] ?? null;

    if ($id) {
        $access_token = getSpotifyAccessToken($client_id, $client_secret);
        if (!$access_token) {
            die("Fehler: Zugriffstoken konnte nicht abgerufen werden.");
        }

        // API-Endpunkt basierend auf Typ
        if ($content_type === 'playlist') {
            $api_url = "https://api.spotify.com/v1/playlists/$id?market=DE";
        } else { // Album oder Hörspiel
            $api_url = "https://api.spotify.com/v1/albums/$id?market=DE";
        }

        $data = getSpotifyData($api_url, $access_token);

        // Daten extrahieren
        if ($content_type === 'playlist') {
            $title = $data['name'] ?? 'Unbekannte Playlist';
            $artist = 'Verschiedenes';
        } else {
            $title = $data['name'] ?? 'Unbekanntes Album';
            $artist = $data['artists'][0]['name'] ?? 'Unbekannter Künstler';
        }
        $cover_url = $data['images'][0]['url'] ?? '';
        $type = ucfirst($content_type);
    } else {
        die("Fehler: Ungültige Spotify-URL.");
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Karteikarte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            form, .btn, h1 {
                display: none;
            }
            body {
                background-color: white;
            }
            .card {
                margin: 0;
                box-shadow: none;
                border: none;
            }
        }

        .card {
            margin: auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4">Spotify Karteikarte</h1>
        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="spotify_url" class="form-label">Spotify Teilen-Link</label>
                <input type="url" id="spotify_url" name="spotify_url" class="form-control" placeholder="https://open.spotify.com/album/..." required>
            </div>
            <div class="mb-3">
                <label for="content_type" class="form-label">Typ</label>
                <select id="content_type" name="content_type" class="form-select" required>
                    <option value="album">Album</option>
                    <option value="playlist">Playlist</option>
                    <option value="hörspiel">Hörspiel</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Karte generieren</button>
        </form>

        <?php if (!empty($title) && !empty($cover_url)): ?>
            <div class="card mx-auto" style="width: 18rem;">
                <img src="<?= htmlspecialchars($cover_url) ?>" class="card-img-top" alt="Album Cover">
                <div class="card-body text-center">
                    <h5 class="card-title"><?= htmlspecialchars($title) ?></h5>
                    <p class="card-text"><?= htmlspecialchars($artist) ?></p>
                    <p class="card-text"><strong><?= htmlspecialchars($type) ?></strong></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
