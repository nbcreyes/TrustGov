<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'official') {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Officials only."]);
    exit;
}

include_once '../config/database.php';
include_once '../audit/log.php';

$database = new Database();
$db = $database->getConnection();

$data     = json_decode(file_get_contents("php://input"), true);
$title    = isset($data['title'])    ? trim($data['title'])    : '';
$body     = isset($data['body'])     ? trim($data['body'])     : '';
$category = isset($data['category']) ? trim($data['category']) : 'info';
$posted_by = $_SESSION['user_id'];
$barangay  = $_SESSION['barangay'];

if (empty($title) || empty($body)) {
    echo json_encode(["status" => "error", "message" => "Title and body are required."]);
    exit;
}

$validCategories = ['info', 'warning', 'urgent'];
if (!in_array($category, $validCategories)) {
    echo json_encode(["status" => "error", "message" => "Invalid category."]);
    exit;
}

$query = "INSERT INTO announcements (title, body, category, barangay_name, posted_by)
          VALUES (:title, :body, :category, :barangay_name, :posted_by)";
$stmt = $db->prepare($query);
$stmt->bindParam(":title",         $title,    PDO::PARAM_STR);
$stmt->bindParam(":body",          $body,     PDO::PARAM_STR);
$stmt->bindParam(":category",      $category, PDO::PARAM_STR);
$stmt->bindParam(":barangay_name", $barangay, PDO::PARAM_STR);
$stmt->bindParam(":posted_by",     $posted_by, PDO::PARAM_INT);
$stmt->execute();

$newId = $db->lastInsertId();

// Notify all citizens in the same barangay
$citizenQuery = "SELECT id FROM users WHERE role = 'citizen' AND barangay = :barangay";
$citizenStmt  = $db->prepare($citizenQuery);
$citizenStmt->bindParam(":barangay", $barangay, PDO::PARAM_STR);
$citizenStmt->execute();
$citizens = $citizenStmt->fetchAll();

foreach ($citizens as $citizen) {
    $notifQuery = "INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)";
    $notifStmt  = $db->prepare($notifQuery);
    $notifStmt->bindParam(":user_id", $citizen['id'], PDO::PARAM_INT);
    $msg = "New announcement: {$title}";
    $notifStmt->bindParam(":message", $msg, PDO::PARAM_STR);
    $notifStmt->execute();
}

logAudit($db, $posted_by, 'CREATE', 'announcements',
    "Posted announcement '{$title}' (category: {$category}) for {$barangay}.");

echo json_encode([
    "status"  => "success",
    "message" => "Announcement posted successfully.",
    "data"    => ["id" => $newId]
]);
?>