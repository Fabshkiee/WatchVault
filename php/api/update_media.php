<?php
session_start();
require_once __DIR__ . '/../config/db_connect.php';

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["message" => "Unauthorized"]);
    exit;
}

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(["message" => "Invalid JSON data"]);
    exit;
}

$conn = getDBConnection();
if (!$conn) {
    echo json_encode(["message" => "Database connection error"]);
    exit;
}

try {
    $query = $conn->prepare("
        UPDATE watchlist
        SET user_review = ?, user_rating = ?, status = ?
        WHERE id = ? AND user_id = ?
    ");

    $query->bind_param(
        "sisii",
        $data["review"],
        $data["rating"],
        $data["status"],
        $data["id"],
        $_SESSION["user_id"]
    );

    if ($query->execute()) {
        echo json_encode(["message" => "Media updated successfully!"]);
    } else {
        throw new Exception("Update failed");
    }
} catch (Exception $e) {
    echo json_encode(["message" => "Update failed"]);
}

$conn->close();
?>