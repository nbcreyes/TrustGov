<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Admin sees all barangays, official and citizen see only their own
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['official', 'citizen'])) {

    // Filter budgets by the logged-in user's barangay
    $query = "SELECT b.id, b.barangay_name, b.fiscal_year, b.total_amount,
                     b.category, b.description, b.created_at,
                     u.full_name AS posted_by_name
              FROM budgets b
              JOIN users u ON b.posted_by = u.id
              WHERE b.barangay_name = :barangay
              ORDER BY b.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":barangay", $_SESSION['barangay']);

} else {

    // Admin fetches all budgets across all barangays
    $query = "SELECT b.id, b.barangay_name, b.fiscal_year, b.total_amount,
                     b.category, b.description, b.created_at,
                     u.full_name AS posted_by_name
              FROM budgets b
              JOIN users u ON b.posted_by = u.id
              ORDER BY b.created_at DESC";
    $stmt = $db->prepare($query);

}

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