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

// Fetch monthly expense totals for the current year
if ($isRestricted) {
    $query = "SELECT DATE_FORMAT(e.expense_date, '%M') AS month,
                     MONTH(e.expense_date)              AS month_num,
                     COALESCE(SUM(e.amount), 0)         AS total_expense
              FROM expenses e
              JOIN projects p ON e.project_id = p.id
              JOIN budgets b  ON p.budget_id  = b.id
              WHERE YEAR(e.expense_date) = YEAR(CURDATE())
              AND b.barangay_name = :barangay
              GROUP BY month_num, month
              ORDER BY month_num ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":barangay", $_SESSION['barangay']);
} else {
    $query = "SELECT DATE_FORMAT(expense_date, '%M') AS month,
                     MONTH(expense_date)              AS month_num,
                     COALESCE(SUM(amount), 0)         AS total_expense
              FROM expenses
              WHERE YEAR(expense_date) = YEAR(CURDATE())
              GROUP BY month_num, month
              ORDER BY month_num ASC";
    $stmt = $db->prepare($query);
}

$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Expense trend retrieved successfully.",
    "data"    => $data
]);
?>