<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Must be logged in to view a user profile
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Please log in.",
        "data"    => null
    ]);
    exit();
}

// Validate that the id parameter is provided
if (empty($_GET['id'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "User ID is required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

// Get the database connection
$database = new Database();
$db = $database->getConnection();

// Fetch a single user by ID, excluding the password field
$query = "SELECT id, full_name, email, role, barangay, created_at
          FROM users
          WHERE id = :id
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
$stmt->execute();

$user = $stmt->fetch();

if ($user) {
    echo json_encode([
        "status"  => "success",
        "message" => "User retrieved successfully.",
        "data"    => $user
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "User not found.",
        "data"    => null
    ]);
}
?>