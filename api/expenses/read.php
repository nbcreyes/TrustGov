<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if filtering by project_id is requested
if (!empty($_GET['project_id'])) {

    // Fetch all expenses under a specific project
    $query = "SELECT e.id, e.description, e.amount, e.supplier,
                     e.receipt_number, e.expense_date, e.created_at,
                     p.project_name, p.status AS project_status,
                     u.full_name AS logged_by_name
              FROM expenses e
              JOIN projects p ON e.project_id = p.id
              JOIN users u    ON e.logged_by  = u.id
              WHERE e.project_id = :project_id
              ORDER BY e.expense_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":project_id", $_GET['project_id'], PDO::PARAM_INT);

} else {

    // Fetch all expenses regardless of project
    $query = "SELECT e.id, e.description, e.amount, e.supplier,
                     e.receipt_number, e.expense_date, e.created_at,
                     p.project_name, p.status AS project_status,
                     u.full_name AS logged_by_name
              FROM expenses e
              JOIN projects p ON e.project_id = p.id
              JOIN users u    ON e.logged_by  = u.id
              ORDER BY e.expense_date DESC";
    $stmt = $db->prepare($query);

}

$stmt->execute();
$expenses = $stmt->fetchAll();

if ($expenses) {
    echo json_encode([
        "status"  => "success",
        "message" => "Expenses retrieved successfully.",
        "data"    => $expenses
    ]);
} else {
    echo json_encode([
        "status"  => "success",
        "message" => "No expenses found.",
        "data"    => []
    ]);
}
?>