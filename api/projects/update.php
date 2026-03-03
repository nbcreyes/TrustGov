<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only officials can update projects
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'official') {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Officials only.",
        "data"    => null
    ]);
    exit();
}

// Read and decode the incoming JSON body
$data = json_decode(file_get_contents("php://input"));

// Validate that all required fields are present
if (
    empty($data->id)               ||
    empty($data->budget_id)        ||
    empty($data->project_name)     ||
    empty($data->allocated_amount) ||
    empty($data->status)
) {
    echo json_encode([
        "status"  => "error",
        "message" => "ID, budget ID, project name, allocated amount, and status are required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if the project exists before updating
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

// Update the project record in the database
$query = "UPDATE projects
          SET budget_id        = :budget_id,
              project_name     = :project_name,
              description      = :description,
              contractor       = :contractor,
              allocated_amount = :allocated_amount,
              spent_amount     = :spent_amount,
              start_date       = :start_date,
              end_date         = :end_date,
              status           = :status
          WHERE id = :id";
$stmt = $db->prepare($query);

$spent_amount = $data->spent_amount ?? 0.00;

$stmt->bindParam(":budget_id",        $data->budget_id,        PDO::PARAM_INT);
$stmt->bindParam(":project_name",     $data->project_name);
$stmt->bindParam(":description",      $data->description);
$stmt->bindParam(":contractor",       $data->contractor);
$stmt->bindParam(":allocated_amount", $data->allocated_amount);
$stmt->bindParam(":spent_amount",     $spent_amount);
$stmt->bindParam(":start_date",       $data->start_date);
$stmt->bindParam(":end_date",         $data->end_date);
$stmt->bindParam(":status",           $data->status);
$stmt->bindParam(":id",               $data->id,               PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Project updated successfully.",
        "data"    => ["id" => $data->id]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to update project. Please try again.",
        "data"    => null
    ]);
}
?>