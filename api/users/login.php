<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Include the database connection
include_once '../config/database.php';

// Read and decode the incoming JSON body
$data = json_decode(file_get_contents("php://input"));

// Validate that email and password are provided
if (empty($data->email) || empty($data->password)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Email and password are required.",
        "data"    => null
    ]);
    exit();
}

// Get the database connection
$database = new Database();
$db = $database->getConnection();

// Fetch the user record matching the provided email
$query = "SELECT id, full_name, email, password, role, barangay FROM users WHERE email = :email LIMIT 1";
$stmt  = $db->prepare($query);
$stmt->bindParam(":email", $data->email);
$stmt->execute();

$user = $stmt->fetch();

// Verify the password against the stored hash
if (!$user || !password_verify($data->password, $user['password'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid email or password.",
        "data"    => null
    ]);
    exit();
}

// Store user info in the session
$_SESSION['user_id']  = $user['id'];
$_SESSION['role']     = $user['role'];
$_SESSION['barangay'] = $user['barangay'];

// Return user data (exclude password)
echo json_encode([
    "status"  => "success",
    "message" => "Login successful.",
    "data"    => [
        "id"        => $user['id'],
        "full_name" => $user['full_name'],
        "email"     => $user['email'],
        "role"      => $user['role'],
        "barangay"  => $user['barangay']
    ]
]);
?>