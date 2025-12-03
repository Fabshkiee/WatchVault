<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("Content-Type: application/json");

error_log("Get watchlist called - User ID: " . ($_SESSION["user_id"] ?? 'not set'));

if (!isset($_SESSION["user_id"])) {
    error_log("Unauthorized access to watchlist");
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized - Please log in again"]);
    exit;
}

$status = $_GET["status"] ?? "all";
$user_id = $_SESSION["user_id"];

error_log("Loading watchlist for user $user_id with status: $status");

$conn = getDBConnection();

if (!$conn) {
    error_log("Database connection failed");
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

try {
    $testQuery = $conn->query("SELECT 1");
    if (!$testQuery) {
        throw new Exception("Database test query failed");
    }

    if ($status === "all") {
        $query = $conn->prepare("
            SELECT 
                id,
                user_id,
                tmdb_movie_id as tmdb_id,
                title,
                poster_path as poster,
                backdrop_path,
                overview,
                release_date,
                media_type as type,
                status,
                user_rating as rating,
                user_review as review,
                added_at,
                updated_at
            FROM watchlist 
            WHERE user_id = ?
            ORDER BY added_at DESC
        ");
        
        if (!$query) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $query->bind_param("i", $user_id);
    } else {
        $query = $conn->prepare("
            SELECT 
                id,
                user_id,
                tmdb_movie_id as tmdb_id,
                title,
                poster_path as poster,
                backdrop_path,
                overview,
                release_date,
                media_type as type,
                status,
                user_rating as rating,
                user_review as review,
                added_at,
                updated_at
            FROM watchlist 
            WHERE user_id = ? AND status = ?
            ORDER BY added_at DESC
        ");
        
        if (!$query) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $query->bind_param("is", $user_id, $status);
    }

    if (!$query->execute()) {
        throw new Exception("Execute failed: " . $query->error);
    }
    
    $result = $query->get_result();
    $watchlist = [];
    
    while ($row = $result->fetch_assoc()) {
        $watchlist[] = $row;
    }
    
    error_log("Successfully loaded " . count($watchlist) . " watchlist items");
    echo json_encode($watchlist);
    
} catch (Exception $e) {
    error_log("Get watchlist error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Failed to load watchlist: " . $e->getMessage()]);
} finally {
    $conn->close();
}
?>