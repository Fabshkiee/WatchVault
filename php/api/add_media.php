<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("Content-Type: application/json");

error_log("=== ADD_MEDIA.PHP STARTED ===");

if (!isset($_SESSION["user_id"])) {
    error_log("Unauthorized: No user_id in session");
    echo json_encode(["success"=>false, "message"=>"Unauthorized"]);
    exit;
}

error_log("User ID: " . $_SESSION["user_id"]);

$input = file_get_contents("php://input");
error_log("Raw input: " . $input);

$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    echo json_encode(["success"=>false, "message"=>"Invalid JSON data: " . json_last_error_msg()]);
    exit;
}

error_log("Decoded data: " . print_r($data, true));

if (!$data || !isset($data["tmdb_id"])) {
    error_log("Missing required fields. Data: " . print_r($data, true));
    echo json_encode(["success"=>false, "message"=>"Invalid request data - missing tmdb_id"]);
    exit;
}

$conn = getDBConnection();

if (!$conn) {
    error_log("Database connection failed");
    echo json_encode(["success"=>false, "message"=>"Database connection failed"]);
    exit;
}

error_log("Database connected successfully");

try {
    // Set default values for optional fields
    $title = $data["title"] ?? 'Unknown Title';
    $poster = $data["poster"] ?? '';
    $type = $data["type"] ?? 'movie';
    $overview = $data["overview"] ?? '';
    $release_date = $data["release_date"] ?? null;
    $backdrop_path = $data["backdrop_path"] ?? '';

    // Log the data we're about to insert
    error_log("Inserting data - Title: $title, Type: $type, TMDB ID: " . $data["tmdb_id"]);

    $query = $conn->prepare("
        INSERT INTO watchlist (user_id, tmdb_movie_id, title, poster_path, backdrop_path, overview, release_date, media_type, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'wantToWatch')
    ");

    if (!$query) {
        error_log("Prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Handle NULL release_date properly
    if (empty($release_date)) {
        $release_date = null;
    }

    $query->bind_param(
        "iissssss",
        $_SESSION["user_id"],
        $data["tmdb_id"],
        $title,
        $poster,
        $backdrop_path,
        $overview,
        $release_date,
        $type
    );

    if ($query->execute()) {
        error_log("Successfully added to watchlist");
        echo json_encode(["success"=>true, "message"=>"Added to your watchlist!"]);
    } else {
        error_log("Execute failed: " . $query->error);
        if ($conn->errno === 1062) {
            error_log("Duplicate entry");
            echo json_encode(["success"=>false, "message"=>"This item is already in your watchlist!"]);
        } else {
            throw new Exception("Execute failed: " . $query->error);
        }
    }
    
} catch (Exception $e) {
    error_log("Add media exception: " . $e->getMessage());
    echo json_encode(["success"=>false, "message"=>"Failed to add to watchlist: " . $e->getMessage()]);
} finally {
    $conn->close();
}

error_log("=== ADD_MEDIA.PHP COMPLETED ===");
?>