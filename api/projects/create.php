<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only officials can create projects
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
    empty($data->budget_id)        ||
    empty($data->project_name)     ||
    empty($data->allocated_amount)
) {
    echo json_encode([
        "status"  => "error",
        "message" => "Budget ID, project name, and allocated amount are required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if the referenced budget exists
$checkQuery = "SELECT id FROM budgets WHERE id = :budget_id LIMIT 1";
$checkStmt  = $db->prepare($checkQuery);
$checkStmt->bindParam(":budget_id", $data->budget_id, PDO::PARAM_INT);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Referenced budget not found.",
        "data"    => null
    ]);
    exit();
}

// Insert the new project record into the database
$query = "INSERT INTO projects
            (budget_id, project_name, description, contractor,
             allocated_amount, spent_amount, start_date, end_date, status, created_by)
          VALUES
            (:budget_id, :project_name, :description, :contractor,
             :allocated_amount, 0.00, :start_date, :end_date, :status, :created_by)";
$stmt = $db->prepare($query);

$status = $data->status ?? 'planned';

$stmt->bindParam(":budget_id",        $data->budget_id,        PDO::PARAM_INT);
$stmt->bindParam(":project_name",     $data->project_name);
$stmt->bindParam(":description",      $data->description);
$stmt->bindParam(":contractor",       $data->contractor);
$stmt->bindParam(":allocated_amount", $data->allocated_amount);
$stmt->bindParam(":start_date",       $data->start_date);
$stmt->bindParam(":end_date",         $data->end_date);
$stmt->bindParam(":status",           $status);
$stmt->bindParam(":created_by",       $_SESSION['user_id'],    PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Project created successfully.",
        "data"    => ["id" => $db->lastInsertId()]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to create project. Please try again.",
        "data"    => null
    ]);
}
?>