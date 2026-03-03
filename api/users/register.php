<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Include the database connection
include_once '../config/database.php';

// Read and decode the incoming JSON body
$data = json_decode(file_get_contents("php://input"));

// Validate that all required fields are present
if (
    empty($data->full_name) ||
    empty($data->email)     ||
    empty($data->password)  ||
    empty($data->role)      ||
    empty($data->barangay)
) {
    echo json_encode([
        "status"  => "error",
        "message" => "All fields are required.",
        "data"    => null
    ]);
    exit();
}

// Get the database connection
$database = new Database();
$db = $database->getConnection();

// Check if the email is already taken
$checkQuery = "SELECT id FROM users WHERE email = :email";
$checkStmt  = $db->prepare($checkQuery);
$checkStmt->bindParam(":email", $data->email);
$checkStmt->execute();

if ($checkStmt->rowCount() > 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Email is already registered.",
        "data"    => null
    ]);
    exit();
}

// Hash the password securely before storing
$hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);

// Insert the new user into the database
$insertQuery = "INSERT INTO users (full_name, email, password, role, barangay)
                VALUES (:full_name, :email, :password, :role, :barangay)";
$insertStmt = $db->prepare($insertQuery);

$insertStmt->bindParam(":full_name", $data->full_name);
$insertStmt->bindParam(":email",     $data->email);
$insertStmt->bindParam(":password",  $hashedPassword);
$insertStmt->bindParam(":role",      $data->role);
$insertStmt->bindParam(":barangay",  $data->barangay);

if ($insertStmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "User registered successfully.",
        "data"    => ["id" => $db->lastInsertId()]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Registration failed. Please try again.",
        "data"    => null
    ]);
}
?>