<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$isRestricted    = isset($_SESSION['role']) && in_array($_SESSION['role'], ['official', 'citizen']);
$hasProjectFilter = !empty($_GET['project_id']);

if ($isRestricted && $hasProjectFilter) {

    // Restricted role + project filter
    $query = "SELECT e.id, e.project_id, e.description, e.amount, e.supplier,
                     e.receipt_number, e.expense_date, e.created_at,
                     p.project_name, p.status AS project_status,
                     b.barangay_name,
                     u.full_name AS logged_by_name
              FROM expenses e
              JOIN projects p ON e.project_id = p.id
              JOIN budgets b  ON p.budget_id   = b.id
              JOIN users u    ON e.logged_by   = u.id
              WHERE e.project_id = :project_id
              AND b.barangay_name = :barangay
              ORDER BY e.expense_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":project_id", $_GET['project_id'], PDO::PARAM_INT);
    $stmt->bindParam(":barangay",   $_SESSION['barangay']);

} elseif ($isRestricted && !$hasProjectFilter) {

    // Restricted role, no project filter — filter by barangay only
    $query = "SELECT e.id, e.project_id, e.description, e.amount, e.supplier,
                     e.receipt_number, e.expense_date, e.created_at,
                     p.project_name, p.status AS project_status,
                     b.barangay_name,
                     u.full_name AS logged_by_name
              FROM expenses e
              JOIN projects p ON e.project_id = p.id
              JOIN budgets b  ON p.budget_id   = b.id
              JOIN users u    ON e.logged_by   = u.id
              WHERE b.barangay_name = :barangay
              ORDER BY e.expense_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":barangay", $_SESSION['barangay']);

} elseif (!$isRestricted && $hasProjectFilter) {

    // Admin + project filter
    $query = "SELECT e.id, e.project_id, e.description, e.amount, e.supplier,
                     e.receipt_number, e.expense_date, e.created_at,
                     p.project_name, p.status AS project_status,
                     b.barangay_name,
                     u.full_name AS logged_by_name
              FROM expenses e
              JOIN projects p ON e.project_id = p.id
              JOIN budgets b  ON p.budget_id   = b.id
              JOIN users u    ON e.logged_by   = u.id
              WHERE e.project_id = :project_id
              ORDER BY e.expense_date DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":project_id", $_GET['project_id'], PDO::PARAM_INT);

} else {

    // Admin, no filter — fetch everything
    $query = "SELECT e.id, e.project_id, e.description, e.amount, e.supplier,
                     e.receipt_number, e.expense_date, e.created_at,
                     p.project_name, p.status AS project_status,
                     b.barangay_name,
                     u.full_name AS logged_by_name
              FROM expenses e
              JOIN projects p ON e.project_id = p.id
              JOIN budgets b  ON p.budget_id   = b.id
              JOIN users u    ON e.logged_by   = u.id
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