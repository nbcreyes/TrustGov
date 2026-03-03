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

$isRestricted = in_array($_SESSION['role'], ['official', 'citizen']);

if ($isRestricted) {
    $query = "SELECT a.id, a.title, a.body, a.category, a.barangay_name,
                     a.created_at, u.full_name AS posted_by_name, u.role AS posted_by_role
              FROM announcements a
              JOIN users u ON a.posted_by = u.id
              WHERE a.barangay_name = :barangay
              ORDER BY a.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":barangay", $_SESSION['barangay'], PDO::PARAM_STR);
} else {
    $query = "SELECT a.id, a.title, a.body, a.category, a.barangay_name,
                     a.created_at, u.full_name AS posted_by_name, u.role AS posted_by_role
              FROM announcements a
              JOIN users u ON a.posted_by = u.id
              ORDER BY a.created_at DESC";
    $stmt = $db->prepare($query);
}

$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Announcements retrieved successfully.",
    "data"    => $data
]);
?>