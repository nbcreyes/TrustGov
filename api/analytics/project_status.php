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

// Fetch project count per status, filtered by barangay if restricted
if ($isRestricted) {
    $query = "SELECT p.status, COUNT(*) AS count
              FROM projects p
              JOIN budgets b ON p.budget_id = b.id
              WHERE b.barangay_name = :barangay
              GROUP BY p.status
              ORDER BY FIELD(p.status, 'planned', 'ongoing', 'completed', 'cancelled')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":barangay", $_SESSION['barangay']);
} else {
    $query = "SELECT status, COUNT(*) AS count
              FROM projects
              GROUP BY status
              ORDER BY FIELD(status, 'planned', 'ongoing', 'completed', 'cancelled')";
    $stmt = $db->prepare($query);
}

$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Project status breakdown retrieved successfully.",
    "data"    => $data
]);
?>