<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$isRestricted = isset($_SESSION['role']) && in_array($_SESSION['role'], ['official', 'citizen']);
$hasBudgetFilter = !empty($_GET['budget_id']);

if ($isRestricted && $hasBudgetFilter) {

    // Restricted role + budget filter
    $query = "SELECT p.id, p.budget_id, p.project_name, p.description, p.contractor,
                     p.allocated_amount, p.spent_amount, p.start_date,
                     p.end_date, p.status, p.created_at,
                     b.barangay_name, b.category AS budget_category,
                     u.full_name AS created_by_name
              FROM projects p
              JOIN budgets b ON p.budget_id = b.id
              JOIN users u   ON p.created_by = u.id
              WHERE p.budget_id = :budget_id
              AND b.barangay_name = :barangay
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":budget_id", $_GET['budget_id'], PDO::PARAM_INT);
    $stmt->bindParam(":barangay",  $_SESSION['barangay']);

} elseif ($isRestricted && !$hasBudgetFilter) {

    // Restricted role, no budget filter — filter by barangay only
    $query = "SELECT p.id, p.budget_id, p.project_name, p.description, p.contractor,
                     p.allocated_amount, p.spent_amount, p.start_date,
                     p.end_date, p.status, p.created_at,
                     b.barangay_name, b.category AS budget_category,
                     u.full_name AS created_by_name
              FROM projects p
              JOIN budgets b ON p.budget_id = b.id
              JOIN users u   ON p.created_by = u.id
              WHERE b.barangay_name = :barangay
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":barangay", $_SESSION['barangay']);

} elseif (!$isRestricted && $hasBudgetFilter) {

    // Admin + budget filter
    $query = "SELECT p.id, p.budget_id, p.project_name, p.description, p.contractor,
                     p.allocated_amount, p.spent_amount, p.start_date,
                     p.end_date, p.status, p.created_at,
                     b.barangay_name, b.category AS budget_category,
                     u.full_name AS created_by_name
              FROM projects p
              JOIN budgets b ON p.budget_id = b.id
              JOIN users u   ON p.created_by = u.id
              WHERE p.budget_id = :budget_id
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":budget_id", $_GET['budget_id'], PDO::PARAM_INT);

} else {

    // Admin, no filter — fetch everything
    $query = "SELECT p.id, p.budget_id, p.project_name, p.description, p.contractor,
                     p.allocated_amount, p.spent_amount, p.start_date,
                     p.end_date, p.status, p.created_at,
                     b.barangay_name, b.category AS budget_category,
                     u.full_name AS created_by_name
              FROM projects p
              JOIN budgets b ON p.budget_id = b.id
              JOIN users u   ON p.created_by = u.id
              ORDER BY p.created_at DESC";
    $stmt = $db->prepare($query);

}

$stmt->execute();
$projects = $stmt->fetchAll();

if ($projects) {
    echo json_encode([
        "status"  => "success",
        "message" => "Projects retrieved successfully.",
        "data"    => $projects
    ]);
} else {
    echo json_encode([
        "status"  => "success",
        "message" => "No projects found.",
        "data"    => []
    ]);
}
?>