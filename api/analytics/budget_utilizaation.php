<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch total allocated budget and total spent per category
$query = "SELECT b.category,
                 COALESCE(SUM(b.total_amount), 0)  AS total_allocated,
                 COALESCE(SUM(p.spent_amount), 0)  AS total_spent
          FROM budgets b
          LEFT JOIN projects p ON p.budget_id = b.id
          GROUP BY b.category
          ORDER BY total_allocated DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Budget utilization retrieved successfully.",
    "data"    => $data
]);
?>