<?php
header("Content-Type: application/json");

// Get parameters
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Validate input
if (empty($q)) {
    echo json_encode(['error' => 'Empty query']);
    exit;
}

// TMDB API configuration
$apiKey = 'af64874a8757259a4dfa63a0d0ab81a2';
$results = [];

try {
    // Encode the query
    $encodedQuery = urlencode($q);
    
    if ($category === 'all' || $category === 'movie') {
        // Search movies
        $movieUrl = "https://api.themoviedb.org/3/search/movie?api_key=$apiKey&query=$encodedQuery";
        $movieResponse = file_get_contents($movieUrl);
        
        if ($movieResponse) {
            $movieData = json_decode($movieResponse, true);
            if (isset($movieData['results'])) {
                foreach ($movieData['results'] as $item) {
                    $item['media_type'] = 'movie';
                    $results[] = $item;
                }
            }
        }
    }
    
    if ($category === 'all' || $category === 'tv') {
        // Search TV shows
        $tvUrl = "https://api.themoviedb.org/3/search/tv?api_key=$apiKey&query=$encodedQuery";
        $tvResponse = file_get_contents($tvUrl);
        
        if ($tvResponse) {
            $tvData = json_decode($tvResponse, true);
            if (isset($tvData['results'])) {
                foreach ($tvData['results'] as $item) {
                    $item['media_type'] = 'tv';
                    $results[] = $item;
                }
            }
        }
    }
    
    // Return results
    echo json_encode($results);
    
} catch (Exception $e) {
    // Return empty array on error
    echo json_encode([]);
}
?>