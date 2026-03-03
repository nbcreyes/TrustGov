<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

// Only officials can create projects
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'official') {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Officials only."]);
    exit;
}

include_once '../config/database.php';
include_once '../audit/log.php';

$database = new Database();
$db = $database->getConnection();

// Parse request body
$data             = json_decode(file_get_contents("php://input"), true);
$budget_id        = isset($data['budget_id'])        ? intval($data['budget_id'])           : 0;
$project_name     = isset($data['project_name'])     ? trim($data['project_name'])           : '';
$description      = isset($data['description'])      ? trim($data['description'])            : '';
$contractor       = isset($data['contractor'])       ? trim($data['contractor'])             : '';
$allocated_amount = isset($data['allocated_amount']) ? floatval($data['allocated_amount'])   : 0;
$start_date       = isset($data['start_date'])       ? trim($data['start_date'])             : null;
$end_date         = isset($data['end_date'])         ? trim($data['end_date'])               : null;
$status           = isset($data['status'])           ? trim($data['status'])                 : 'planned';
$created_by       = $_SESSION['user_id'];

// Validate required fields
if (!$budget_id || empty($project_name) || !$allocated_amount) {
    echo json_encode(["status" => "error", "message" => "Budget, project name, and allocated amount are required."]);
    exit;
}

// Validate status
$validStatuses = ['planned', 'ongoing', 'completed', 'cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(["status" => "error", "message" => "Invalid status."]);
    exit;
}

// Validate allocated amount
if ($allocated_amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Allocated amount must be greater than zero."]);
    exit;
}

// Verify the budget exists
$checkQuery = "SELECT id, barangay_name, category FROM budgets WHERE id = :budget_id LIMIT 1";
$checkStmt  = $db->prepare($checkQuery);
$checkStmt->bindParam(":budget_id", $budget_id, PDO::PARAM_INT);
$checkStmt->execute();
$budget = $checkStmt->fetch();

if (!$budget) {
    echo json_encode(["status" => "error", "message" => "Budget not found."]);
    exit;
}

// Handle empty dates
$start_date = !empty($start_date) ? $start_date : null;
$end_date   = !empty($end_date)   ? $end_date   : null;

// Insert project
$insertQuery = "INSERT INTO projects
                    (budget_id, project_name, description, contractor,
                     allocated_amount, start_date, end_date, status, created_by)
                VALUES
                    (:budget_id, :project_name, :description, :contractor,
                     :allocated_amount, :start_date, :end_date, :status, :created_by)";
$insertStmt = $db->prepare($insertQuery);
$insertStmt->bindParam(":budget_id",        $budget_id,        PDO::PARAM_INT);
$insertStmt->bindParam(":project_name",     $project_name,     PDO::PARAM_STR);
$insertStmt->bindParam(":description",      $description,      PDO::PARAM_STR);
$insertStmt->bindParam(":contractor",       $contractor,       PDO::PARAM_STR);
$insertStmt->bindParam(":allocated_amount", $allocated_amount);
$insertStmt->bindParam(":start_date",       $start_date);
$insertStmt->bindParam(":end_date",         $end_date);
$insertStmt->bindParam(":status",           $status,           PDO::PARAM_STR);
$insertStmt->bindParam(":created_by",       $created_by,       PDO::PARAM_INT);
$insertStmt->execute();

$newId = $db->lastInsertId();

// Log the action to audit trail
logAudit(
    $db,
    $created_by,
    'CREATE',
    'projects',
    "Created project '{$project_name}' under budget ID {$budget_id} ({$budget['barangay_name']}, {$budget['category']}). Allocated: ₱" . number_format($allocated_amount, 2) . "."
);

echo json_encode([
    "status"  => "success",
    "message" => "Project created successfully.",
    "data"    => ["id" => $newId]
]);
?>