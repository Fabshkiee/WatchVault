<?php
header("Content-Type: application/json");

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'movie';

if (empty($id)) {
    echo json_encode(['error' => 'Missing id']);
    exit;
}

// TMDB API key (same as search_tmdb.php)
$apiKey = 'af64874a8757259a4dfa63a0d0ab81a2';

$allowedTypes = ['movie', 'tv'];
if (!in_array($type, $allowedTypes)) $type = 'movie';

$url = "https://api.themoviedb.org/3/" . ($type === 'tv' ? 'tv' : 'movie') . "/" . urlencode($id) . "?api_key={$apiKey}&language=en-US&append_to_response=credits,release_dates,alternative_titles";

try {
    $response = @file_get_contents($url);
    if ($response === false) {
        echo json_encode(['error' => 'Failed to fetch from TMDB']);
        exit;
    }
    $data = json_decode($response, true);
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>