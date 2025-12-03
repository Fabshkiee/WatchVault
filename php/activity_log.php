<?php
// php/functions/activity_log.php
function logActivity($conn, $user_id, $action_type, $description) {
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action_type, $description);
    $stmt->execute();
    $stmt->close();
}
?>