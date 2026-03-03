<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Validate that the id parameter is provided
if (empty($_GET['id'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Project ID is required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch a single project by ID with budget and creator details
$query = "SELECT p.id, p.project_name, p.description, p.contractor,
                 p.allocated_amount, p.spent_amount, p.start_date,
                 p.end_date, p.status, p.created_at,
                 b.id AS budget_id, b.barangay_name, b.fiscal_year,
                 b.category AS budget_category,
                 u.full_name AS created_by_name
          FROM projects p
          JOIN budgets b ON p.budget_id = b.id
          JOIN users u   ON p.created_by = u.id
          WHERE p.id = :id
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
$stmt->execute();

$project = $stmt->fetch();

if ($project) {
    echo json_encode([
        "status"  => "success",
        "message" => "Project retrieved successfully.",
        "data"    => $project
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Project not found.",
        "data"    => null
    ]);
}
?>