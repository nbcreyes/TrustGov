<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only citizens can submit feedback
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'citizen') {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Citizens only.",
        "data"    => null
    ]);
    exit();
}

// Read and decode the incoming JSON body
$data = json_decode(file_get_contents("php://input"));

// Validate that all required fields are present
if (empty($data->project_id) || empty($data->comment)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Project ID and comment are required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if the referenced project exists
$checkQuery = "SELECT id FROM projects WHERE id = :project_id LIMIT 1";
$checkStmt  = $db->prepare($checkQuery);
$checkStmt->bindParam(":project_id", $data->project_id, PDO::PARAM_INT);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Referenced project not found.",
        "data"    => null
    ]);
    exit();
}

// Insert the new feedback record into the database
$query = "INSERT INTO feedback (project_id, citizen_id, comment, status)
          VALUES (:project_id, :citizen_id, :comment, 'pending')";
$stmt = $db->prepare($query);

$stmt->bindParam(":project_id", $data->project_id, PDO::PARAM_INT);
$stmt->bindParam(":citizen_id", $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindParam(":comment",    $data->comment);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Feedback submitted successfully.",
        "data"    => ["id" => $db->lastInsertId()]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to submit feedback. Please try again.",
        "data"    => null
    ]);
}
?>