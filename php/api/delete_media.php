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
    $query = $conn->prepare("DELETE FROM watchlist WHERE id = ? AND user_id = ?");
    $query->bind_param("ii", $data["id"], $_SESSION["user_id"]);

    if ($query->execute()) {
        echo json_encode(["success"=>true, "message"=>"Media removed from your library"]);
    } else {
        throw new Exception("Delete failed");
    }
} catch (Exception $e) {
    echo json_encode(["success"=>false, "message"=>"Failed to delete media"]);
}

$conn->close();
?>