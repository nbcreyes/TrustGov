<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized.", "data" => []]);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, message, is_read, created_at
          FROM notifications
          WHERE user_id = :user_id
          ORDER BY created_at DESC
          LIMIT 20";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Notifications retrieved.",
    "data"    => $data
]);
?>