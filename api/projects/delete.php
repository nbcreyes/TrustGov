<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only admin can delete projects
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Admins only.",
        "data"    => null
    ]);
    exit();
}

// Read and decode the incoming JSON body
$data = json_decode(file_get_contents("php://input"));

// Validate that the id is provided
if (empty($data->id)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Project ID is required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if the project exists before attempting delete
$checkQuery = "SELECT id FROM projects WHERE id = :id LIMIT 1";
$checkStmt  = $db->prepare($checkQuery);
$checkStmt->bindParam(":id", $data->id, PDO::PARAM_INT);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Project not found.",
        "data"    => null
    ]);
    exit();
}

// Delete the project record by ID
$query = "DELETE FROM projects WHERE id = :id";
$stmt  = $db->prepare($query);
$stmt->bindParam(":id", $data->id, PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Project deleted successfully.",
        "data"    => ["id" => $data->id]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to delete project. Please try again.",
        "data"    => null
    ]);
}
?>