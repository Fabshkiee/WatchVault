<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success"=>false, "message"=>"Unauthorized"]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success"=>false, "message"=>"Invalid JSON data"]);
    exit;
}

$conn = getDBConnection();

if (!$conn) {
    echo json_encode(["success"=>false, "message"=>"Database connection failed"]);
    exit;
}

try {
    // First, get the media details before deleting
    $media_title = '';
    $media_type = '';
    
    $query = $conn->prepare("SELECT title, media_type FROM watchlist WHERE id = ? AND user_id = ?");
    $query->bind_param("ii", $data["id"], $_SESSION["user_id"]);
    $query->execute();
    $query->bind_result($media_title, $media_type);
    
    if (!$query->fetch()) {
        throw new Exception("Media not found or unauthorized");
    }
    
    $query->close();

    // Now delete the media
    $query = $conn->prepare("DELETE FROM watchlist WHERE id = ? AND user_id = ?");
    $query->bind_param("ii", $data["id"], $_SESSION["user_id"]);

    if ($query->execute()) {
        // Log the deletion activity
        $activityStmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (?, 'delete_from_watchlist', ?)");
        $activityDesc = "Removed '{$media_title}' ({$media_type}) from watchlist";
        $activityStmt->bind_param("is", $_SESSION["user_id"], $activityDesc);
        $activityStmt->execute();
        $activityStmt->close();
        
        echo json_encode(["success"=>true, "message"=>"Media removed from your library"]);
    } else {
        throw new Exception("Delete failed");
    }
} catch (Exception $e) {
    echo json_encode(["success"=>false, "message"=>"Failed to delete media: " . $e->getMessage()]);
}

$conn->close();
?>