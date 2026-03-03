<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized.", "data" => []]);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$isRestricted = in_array($_SESSION['role'], ['official', 'citizen']);
$barangay     = $isRestricted ? $_SESSION['barangay'] : null;

// Helper to bind barangay if restricted
function runQuery($db, $query, $barangay = null) {
    $stmt = $db->prepare($query);
    if ($barangay) $stmt->bindParam(":barangay", $barangay, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt;
}

$bWhere = $barangay ? "WHERE b.barangay_name = :barangay" : "";
$pWhere = $barangay ? "WHERE b.barangay_name = :barangay" : "";

// 1. Budget summary per category
$budgetQuery = "SELECT b.category,
                       COUNT(*) AS budget_count,
                       SUM(b.total_amount) AS total_allocated,
                       COALESCE(SUM(p.spent_amount),0) AS total_spent
                FROM budgets b
                LEFT JOIN projects p ON p.budget_id = b.id
                $bWhere
                GROUP BY b.category ORDER BY total_allocated DESC";
$budgetData = runQuery($db, $budgetQuery, $barangay)->fetchAll();

// 2. Project breakdown by status
$projectQuery = "SELECT p.status, COUNT(*) AS count,
                        SUM(p.allocated_amount) AS total_allocated,
                        SUM(p.spent_amount) AS total_spent
                 FROM projects p
                 JOIN budgets b ON p.budget_id = b.id
                 $pWhere
                 GROUP BY p.status
                 ORDER BY FIELD(p.status,'planned','ongoing','completed','cancelled')";
$projectData = runQuery($db, $projectQuery, $barangay)->fetchAll();

// 3. Top 5 expenses
$expenseQuery = "SELECT e.description, e.amount, e.expense_date,
                        e.supplier, p.project_name
                 FROM expenses e
                 JOIN projects p ON e.project_id = p.id
                 JOIN budgets b  ON p.budget_id  = b.id
                 " . ($barangay ? "WHERE b.barangay_name = :barangay" : "") . "
                 ORDER BY e.amount DESC LIMIT 5";
$expenseData = runQuery($db, $expenseQuery, $barangay)->fetchAll();

// 4. Feedback stats
$feedbackQuery = "SELECT
                      COUNT(*) AS total,
                      SUM(CASE WHEN status='resolved' THEN 1 ELSE 0 END) AS resolved,
                      SUM(CASE WHEN status='pending'  THEN 1 ELSE 0 END) AS pending,
                      SUM(CASE WHEN flag_suspicious=1 THEN 1 ELSE 0 END) AS flagged
                  FROM feedback f
                  JOIN projects p ON f.project_id = p.id
                  JOIN budgets b  ON p.budget_id  = b.id
                  " . ($barangay ? "WHERE b.barangay_name = :barangay" : "");
$feedbackData = runQuery($db, $feedbackQuery, $barangay)->fetch();

// 5. Monthly expense trend
$trendQuery = "SELECT DATE_FORMAT(e.expense_date,'%M') AS month,
                      MONTH(e.expense_date) AS month_num,
                      SUM(e.amount) AS total
               FROM expenses e
               JOIN projects p ON e.project_id = p.id
               JOIN budgets b  ON p.budget_id  = b.id
               " . ($barangay ? "WHERE b.barangay_name = :barangay AND YEAR(e.expense_date) = YEAR(CURDATE())"
                              : "WHERE YEAR(e.expense_date) = YEAR(CURDATE())") . "
               GROUP BY month_num, month ORDER BY month_num";
$trendData = runQuery($db, $trendQuery, $barangay)->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Report generated successfully.",
    "data"    => [
        "barangay"       => $barangay ?? "All Barangays",
        "generated_at"   => date("Y-m-d H:i:s"),
        "budget_summary" => $budgetData,
        "project_status" => $projectData,
        "top_expenses"   => $expenseData,
        "feedback_stats" => $feedbackData,
        "expense_trend"  => $trendData
    ]
]);
?>