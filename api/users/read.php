<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only admin can view all users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Admins only.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

// Get the database connection
$database = new Database();
$db = $database->getConnection();

// Fetch all users, excluding passwords
$query = "SELECT id, full_name, email, role, barangay, created_at
          FROM users
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$users = $stmt->fetchAll();

if ($users) {
    echo json_encode([
        "status"  => "success",
        "message" => "Users retrieved successfully.",
        "data"    => $users
    ]);
} else {
    echo json_encode([
        "status"  => "success",
        "message" => "No users found.",
        "data"    => []
    ]);
}
?>