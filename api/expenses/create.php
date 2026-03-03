<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only officials can log expenses
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
    empty($data->project_id)   ||
    empty($data->description)  ||
    empty($data->amount)       ||
    empty($data->expense_date)
) {
    echo json_encode([
        "status"  => "error",
        "message" => "Project ID, description, amount, and expense date are required.",
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

// Insert the new expense record into the database
$query = "INSERT INTO expenses
            (project_id, description, amount, supplier,
             receipt_number, expense_date, logged_by)
          VALUES
            (:project_id, :description, :amount, :supplier,
             :receipt_number, :expense_date, :logged_by)";
$stmt = $db->prepare($query);

$stmt->bindParam(":project_id",     $data->project_id,      PDO::PARAM_INT);
$stmt->bindParam(":description",    $data->description);
$stmt->bindParam(":amount",         $data->amount);
$stmt->bindParam(":supplier",       $data->supplier);
$stmt->bindParam(":receipt_number", $data->receipt_number);
$stmt->bindParam(":expense_date",   $data->expense_date);
$stmt->bindParam(":logged_by",      $_SESSION['user_id'],   PDO::PARAM_INT);

if ($stmt->execute()) {
    // Update the spent_amount in the projects table to reflect the new expense
    $updateQuery = "UPDATE projects
                    SET spent_amount = spent_amount + :amount
                    WHERE id = :project_id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(":amount",     $data->amount);
    $updateStmt->bindParam(":project_id", $data->project_id, PDO::PARAM_INT);
    $updateStmt->execute();

    echo json_encode([
        "status"  => "success",
        "message" => "Expense logged successfully.",
        "data"    => ["id" => $db->lastInsertId()]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to log expense. Please try again.",
        "data"    => null
    ]);
}
?>