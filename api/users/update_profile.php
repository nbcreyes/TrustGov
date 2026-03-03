<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
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

$data         = json_decode(file_get_contents("php://input"), true);
$user_id      = $_SESSION['user_id'];
$full_name    = isset($data['full_name']) ? trim($data['full_name']) : '';
$email        = isset($data['email'])     ? trim($data['email'])     : '';
$barangay     = isset($data['barangay'])  ? trim($data['barangay'])  : '';
$current_pass = isset($data['current_password']) ? $data['current_password'] : '';
$new_pass     = isset($data['new_password'])     ? $data['new_password']     : '';

if (empty($full_name) || empty($email) || empty($barangay)) {
    echo json_encode(["status" => "error", "message" => "Name, email, and barangay are required."]);
    exit;
}

// Check email uniqueness (exclude current user)
$emailCheck = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id LIMIT 1");
$emailCheck->bindParam(":email",   $email,   PDO::PARAM_STR);
$emailCheck->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$emailCheck->execute();
if ($emailCheck->fetch()) {
    echo json_encode(["status" => "error", "message" => "Email is already in use by another account."]);
    exit;
}

// If changing password, verify current password first
if (!empty($new_pass)) {
    if (strlen($new_pass) < 6) {
        echo json_encode(["status" => "error", "message" => "New password must be at least 6 characters."]);
        exit;
    }
    $passCheck = $db->prepare("SELECT password FROM users WHERE id = :user_id LIMIT 1");
    $passCheck->bindParam(":user_id", $user_id, PDO::PARAM_INT);
    $passCheck->execute();
    $row = $passCheck->fetch();
    if (!$row || !password_verify($current_pass, $row['password'])) {
        echo json_encode(["status" => "error", "message" => "Current password is incorrect."]);
        exit;
    }
    $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
    $query  = "UPDATE users SET full_name = :full_name, email = :email,
               barangay = :barangay, password = :password WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":password", $hashed, PDO::PARAM_STR);
} else {
    $query = "UPDATE users SET full_name = :full_name, email = :email,
              barangay = :barangay WHERE id = :user_id";
    $stmt = $db->prepare($query);
}

$stmt->bindParam(":full_name", $full_name, PDO::PARAM_STR);
$stmt->bindParam(":email",     $email,     PDO::PARAM_STR);
$stmt->bindParam(":barangay",  $barangay,  PDO::PARAM_STR);
$stmt->bindParam(":user_id",   $user_id,   PDO::PARAM_INT);
$stmt->execute();

// Update session
$_SESSION['barangay'] = $barangay;

// Fetch updated user
$fetchQuery = "SELECT id, full_name, email, role, barangay FROM users WHERE id = :user_id LIMIT 1";
$fetchStmt  = $db->prepare($fetchQuery);
$fetchStmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
$fetchStmt->execute();
$updatedUser = $fetchStmt->fetch();

logAudit($db, $user_id, 'UPDATE', 'users', "Updated own profile. Name: {$full_name}, Email: {$email}, Barangay: {$barangay}.");

echo json_encode([
    "status"  => "success",
    "message" => "Profile updated successfully.",
    "data"    => $updatedUser
]);
?>