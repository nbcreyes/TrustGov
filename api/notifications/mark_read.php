<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized."]);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "UPDATE notifications
          SET is_read = 1
          WHERE user_id = :user_id AND is_read = 0";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();

$affected = $stmt->rowCount();

echo json_encode([
    "status"  => "success",
    "message" => "All notifications marked as read.",
    "data"    => ["updated" => $affected]
]);
?>