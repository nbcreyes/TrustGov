<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Must be logged in to update a profile
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Please log in.",
        "data"    => null
    ]);
    exit();
}

// Read and decode the incoming JSON body
$data = json_decode(file_get_contents("php://input"));

// Validate that required fields are present
if (empty($data->id) || empty($data->full_name) || empty($data->email) || empty($data->barangay)) {
    echo json_encode([
        "status"  => "error",
        "message" => "ID, full name, email, and barangay are required.",
        "data"    => null
    ]);
    exit();
}

// Non-admin users can only update their own profile
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $data->id) {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. You can only update your own profile.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

// Get the database connection
$database = new Database();
$db = $database->getConnection();

// Build the update query dynamically based on whether password is being changed
if (!empty($data->password)) {
    // Hash the new password if provided
    $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);

    $query = "UPDATE users
              SET full_name = :full_name,
                  email     = :email,
                  barangay  = :barangay,
                  password  = :password
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":password", $hashedPassword);
} else {
    // Update without changing the password
    $query = "UPDATE users
              SET full_name = :full_name,
                  email     = :email,
                  barangay  = :barangay
              WHERE id = :id";
    $stmt = $db->prepare($query);
}

$stmt->bindParam(":full_name", $data->full_name);
$stmt->bindParam(":email",     $data->email);
$stmt->bindParam(":barangay",  $data->barangay);
$stmt->bindParam(":id",        $data->id, PDO::PARAM_INT);

// Admin can also update the user's role
if ($_SESSION['role'] === 'admin' && !empty($data->role)) {
    $roleQuery = "UPDATE users SET role = :role WHERE id = :id";
    $roleStmt  = $db->prepare($roleQuery);
    $roleStmt->bindParam(":role", $data->role);
    $roleStmt->bindParam(":id",   $data->id, PDO::PARAM_INT);
    $roleStmt->execute();
}

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "User updated successfully.",
        "data"    => ["id" => $data->id]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Update failed. Please try again.",
        "data"    => null
    ]);
}
?>