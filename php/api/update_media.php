<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data"]);
    exit;
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Database connection error"]);
    exit;
}

try {
    // Get current media title before update for logging
    $current_title = '';
    $title_stmt = $conn->prepare("SELECT title FROM watchlist WHERE id = ? AND user_id = ?");
    $title_stmt->bind_param("ii", $data["id"], $_SESSION["user_id"]);
    $title_stmt->execute();
    $title_stmt->bind_result($current_title);
    $title_stmt->fetch();
    $title_stmt->close();

    // Prepare update query
    $query = $conn->prepare("
        UPDATE watchlist
        SET user_review = ?, user_rating = ?, status = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND user_id = ?
    ");

    $rating = isset($data["rating"]) ? (float)$data["rating"] : null;
    $review = $data["review"] ?? '';
    $status = $data["status"] ?? 'wantToWatch';
    
    $query->bind_param(
        "sisii",
        $review,
        $rating,
        $status,
        $data["id"],
        $_SESSION["user_id"]
    );

    if ($query->execute()) {
        // Log activity with formal message
        $activityStmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (?, ?, ?)");
        
        // Determine the type of activity
        $action_type = 'update_status';
        $activityDesc = "Updated '{$current_title}' to {$status}";
        
        if ($status === 'finished' && $rating) {
            $action_type = 'rate_item';
            $activityDesc = "Rated '{$current_title}' {$rating}/10";
        } elseif (!empty($review)) {
            $action_type = 'review_item';
            $activityDesc = "Reviewed '{$current_title}'";
        }
        
        $activityStmt->bind_param("iss", $_SESSION["user_id"], $action_type, $activityDesc);
        $activityStmt->execute();
        $activityStmt->close();
        
        echo json_encode(["success" => true, "message" => "Media updated successfully!"]);
    } else {
        throw new Exception("Update failed");
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Update failed: " . $e->getMessage()]);
}

$conn->close();
?>