<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch total expenses grouped by month for the current year
$query = "SELECT DATE_FORMAT(expense_date, '%M') AS month,
                 MONTH(expense_date)              AS month_num,
                 COALESCE(SUM(amount), 0)         AS total_expense
          FROM expenses
          WHERE YEAR(expense_date) = YEAR(CURDATE())
          GROUP BY month_num, month
          ORDER BY month_num ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Expense trend retrieved successfully.",
    "data"    => $data
]);
?>