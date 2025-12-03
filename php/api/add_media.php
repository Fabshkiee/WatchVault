<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("Content-Type: application/json");

// error_log("=== ADD_MEDIA.PHP STARTED ===");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success"=>false, "message"=>"Unauthorized"]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success"=>false, "message"=>"Invalid JSON data: " . json_last_error_msg()]);
    exit;
}

if (!$data || !isset($data["tmdb_id"])) {
    echo json_encode(["success"=>false, "message"=>"Invalid request data - missing tmdb_id"]);
    exit;
}

$conn = getDBConnection();

if (!$conn) {
    echo json_encode(["success"=>false, "message"=>"Database connection failed"]);
    exit;
}

try {
    // Set default values for optional fields
    $title = $data["title"] ?? 'Unknown Title';
    $poster = $data["poster"] ?? '';
    $type = $data["type"] ?? 'movie';
    $overview = $data["overview"] ?? '';
    $release_date = $data["release_date"] ?? null;
    $backdrop_path = $data["backdrop"] ?? '';
    $status = $data["status"] ?? 'wantToWatch';
    $rating = isset($data["rating"]) ? (float)$data["rating"] : null;
    $review = $data["review"] ?? '';

    // First, check if item already exists for this user (Upsert Logic)
    $checkStmt = $conn->prepare("SELECT id FROM watchlist WHERE user_id = ? AND tmdb_movie_id = ? AND media_type = ?");
    $checkStmt->bind_param("iis", $_SESSION["user_id"], $data["tmdb_id"], $type);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Item exists, update it
        $row = $checkResult->fetch_assoc();
        $existingId = $row['id'];
        
        $query = $conn->prepare("
            UPDATE watchlist 
            SET title = ?, 
                poster_path = ?, 
                backdrop_path = ?, 
                overview = ?, 
                release_date = ?, 
                status = ?,
                user_rating = ?,
                user_review = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");

        if (!$query) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if (empty($release_date)) {
            $release_date = null;
        }

        // FIX: Changed rating binding from 'i' (int) to 'd' (double)
        $query->bind_param(
            "ssssssdsii",
            $title,
            $poster,
            $backdrop_path,
            $overview,
            $release_date,
            $status,
            $rating, // 'd' for double/decimal
            $review,
            $existingId,
            $_SESSION["user_id"]
        );

        if ($query->execute()) {
            // Log activity
            $activityStmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (?, 'update_status', ?)");
            $activityDesc = "Updated '{$title}' to {$status}";
            if ($status === 'finished' && $rating) {
                $activityDesc .= " with rating: {$rating}/10";
            }
            $activityStmt->bind_param("is", $_SESSION["user_id"], $activityDesc);
            $activityStmt->execute();
            $activityStmt->close();
            
            echo json_encode(["success"=>true, "message"=>"Updated in your watchlist!"]);
        } else {
            throw new Exception("Update failed: " . $query->error);
        }
        
        $query->close();
    } else {
        // Item doesn't exist, insert new
        $query = $conn->prepare("
            INSERT INTO watchlist (user_id, tmdb_movie_id, title, poster_path, backdrop_path, overview, release_date, media_type, status, user_rating, user_review)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$query) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        if (empty($release_date)) {
            $release_date = null;
        }

        // FIX: Changed rating binding from 'i' (int) to 'd' (double)
        $query->bind_param(
            "iisssssssds",
            $_SESSION["user_id"],
            $data["tmdb_id"],
            $title,
            $poster,
            $backdrop_path,
            $overview,
            $release_date,
            $type,
            $status,
            $rating, // 'd' for double
            $review
        );

        if ($query->execute()) {
            // Log activity
            $activityStmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (?, 'add_to_watchlist', ?)");
            $activityDesc = "Added '{$title}' ({$type}) to watchlist";
            $activityStmt->bind_param("is", $_SESSION["user_id"], $activityDesc);
            $activityStmt->execute();
            $activityStmt->close();
            
            echo json_encode(["success"=>true, "message"=>"Added to your watchlist!"]);
        } else {
            if ($conn->errno === 1062) {
                echo json_encode(["success"=>false, "message"=>"This item is already in your watchlist!"]);
            } else {
                throw new Exception("Execute failed: " . $query->error);
            }
        }
        
        $query->close();
    }
    
    $checkStmt->close();
    
} catch (Exception $e) {
    error_log("Add media exception: " . $e->getMessage());
    echo json_encode(["success"=>false, "message"=>"Failed to add to watchlist: " . $e->getMessage()]);
} finally {
    $conn->close();
}
?>