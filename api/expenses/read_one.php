<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Validate that the id parameter is provided
if (empty($_GET['id'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Expense ID is required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch a single expense by ID with project and logger details
$query = "SELECT e.id, e.description, e.amount, e.supplier,
                 e.receipt_number, e.expense_date, e.created_at,
                 p.id AS project_id, p.project_name, p.status AS project_status,
                 u.full_name AS logged_by_name
          FROM expenses e
          JOIN projects p ON e.project_id = p.id
          JOIN users u    ON e.logged_by  = u.id
          WHERE e.id = :id
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
$stmt->execute();

$expense = $stmt->fetch();

if ($expense) {
    echo json_encode([
        "status"  => "success",
        "message" => "Expense retrieved successfully.",
        "data"    => $expense
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Expense not found.",
        "data"    => null
    ]);
}
?>