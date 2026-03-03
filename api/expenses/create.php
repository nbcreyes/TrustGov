<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

// Only officials can log expenses
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'official') {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Officials only."]);
    exit;
}

include_once '../config/database.php';
include_once '../audit/log.php';

$database = new Database();
$db = $database->getConnection();

// Parse request body
$data           = json_decode(file_get_contents("php://input"), true);
$project_id     = isset($data['project_id'])     ? intval($data['project_id'])         : 0;
$description    = isset($data['description'])    ? trim($data['description'])           : '';
$amount         = isset($data['amount'])         ? floatval($data['amount'])            : 0;
$expense_date   = isset($data['expense_date'])   ? trim($data['expense_date'])          : '';
$supplier       = isset($data['supplier'])       ? trim($data['supplier'])              : '';
$receipt_number = isset($data['receipt_number']) ? trim($data['receipt_number'])        : '';
$logged_by      = $_SESSION['user_id'];

// Validate required fields
if (!$project_id || empty($description) || !$amount || empty($expense_date)) {
    echo json_encode(["status" => "error", "message" => "Project, description, amount, and date are required."]);
    exit;
}

// Validate amount
if ($amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Amount must be greater than zero."]);
    exit;
}

// Verify the project exists
$checkQuery = "SELECT p.id, p.project_name, p.allocated_amount, p.spent_amount,
                      b.barangay_name
               FROM projects p
               JOIN budgets b ON p.budget_id = b.id
               WHERE p.id = :project_id
               LIMIT 1";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(":project_id", $project_id, PDO::PARAM_INT);
$checkStmt->execute();
$project = $checkStmt->fetch();

if (!$project) {
    echo json_encode(["status" => "error", "message" => "Project not found."]);
    exit;
}

// Insert expense record
$insertQuery = "INSERT INTO expenses
                    (project_id, description, amount, supplier,
                     receipt_number, expense_date, logged_by)
                VALUES
                    (:project_id, :description, :amount, :supplier,
                     :receipt_number, :expense_date, :logged_by)";
$insertStmt = $db->prepare($insertQuery);
$insertStmt->bindParam(":project_id",     $project_id,     PDO::PARAM_INT);
$insertStmt->bindParam(":description",    $description,    PDO::PARAM_STR);
$insertStmt->bindParam(":amount",         $amount);
$insertStmt->bindParam(":supplier",       $supplier,       PDO::PARAM_STR);
$insertStmt->bindParam(":receipt_number", $receipt_number, PDO::PARAM_STR);
$insertStmt->bindParam(":expense_date",   $expense_date,   PDO::PARAM_STR);
$insertStmt->bindParam(":logged_by",      $logged_by,      PDO::PARAM_INT);
$insertStmt->execute();

$newId = $db->lastInsertId();

// Update project spent_amount
$updateQuery = "UPDATE projects
                SET spent_amount = spent_amount + :amount
                WHERE id = :project_id";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(":amount",     $amount,     PDO::PARAM_STR);
$updateStmt->bindParam(":project_id", $project_id, PDO::PARAM_INT);
$updateStmt->execute();

// Log the action to audit trail
logAudit(
    $db,
    $logged_by,
    'CREATE',
    'expenses',
    "Logged expense of ₱" . number_format($amount, 2) . " for project '{$project['project_name']}' (ID: {$project_id}). Supplier: " . ($supplier ?: 'N/A') . ". Receipt: " . ($receipt_number ?: 'N/A') . "."
);

echo json_encode([
    "status"  => "success",
    "message" => "Expense logged successfully.",
    "data"    => ["id" => $newId]
]);
?>