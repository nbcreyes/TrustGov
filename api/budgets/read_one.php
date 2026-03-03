<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch all budgets joined with the poster's name, ordered by newest first
$query = "SELECT b.id, b.barangay_name, b.fiscal_year, b.total_amount,
                 b.category, b.description, b.created_at,
                 u.full_name AS posted_by_name
          FROM budgets b
          JOIN users u ON b.posted_by = u.id
          ORDER BY b.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$budgets = $stmt->fetchAll();

if ($budgets) {
    echo json_encode([
        "status"  => "success",
        "message" => "Budgets retrieved successfully.",
        "data"    => $budgets
    ]);
} else {
    echo json_encode([
        "status"  => "success",
        "message" => "No budgets found.",
        "data"    => []
    ]);
}
?>