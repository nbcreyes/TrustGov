<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized."]);
    exit;
}

include_once '../config/database.php';
include_once '../audit/log.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);
$id   = isset($data['id']) ? intval($data['id']) : 0;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "Announcement ID is required."]);
    exit;
}

// Fetch announcement
$check = $db->prepare("SELECT id, title, posted_by FROM announcements WHERE id = :id LIMIT 1");
$check->bindParam(":id", $id, PDO::PARAM_INT);
$check->execute();
$announcement = $check->fetch();

if (!$announcement) {
    echo json_encode(["status" => "error", "message" => "Announcement not found."]);
    exit;
}

// Only admin or the official who posted it can delete
if ($_SESSION['role'] !== 'admin' && $announcement['posted_by'] != $_SESSION['user_id']) {
    echo json_encode(["status" => "error", "message" => "You can only delete your own announcements."]);
    exit;
}

$stmt = $db->prepare("DELETE FROM announcements WHERE id = :id");
$stmt->bindParam(":id", $id, PDO::PARAM_INT);
$stmt->execute();

logAudit($db, $_SESSION['user_id'], 'DELETE', 'announcements',
    "Deleted announcement '{$announcement['title']}' (ID: {$id}).");

echo json_encode([
    "status"  => "success",
    "message" => "Announcement deleted successfully."
]);
?>