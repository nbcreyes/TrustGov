<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if filtering by budget_id is requested
if (!empty($_GET['budget_id'])) {

    // Fetch all projects under a specific budget
    $query = "SELECT p.id, p.project_name, p.description, p.contractor,
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

    // Fetch all projects regardless of budget
    $query = "SELECT p.id, p.project_name, p.description, p.contractor,
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