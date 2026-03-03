<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch the total allocated budget across all budget entries
$totalBudgetQuery = "SELECT COALESCE(SUM(total_amount), 0) AS total_budget FROM budgets";
$totalBudgetStmt  = $db->prepare($totalBudgetQuery);
$totalBudgetStmt->execute();
$totalBudget = $totalBudgetStmt->fetch()['total_budget'];

// Fetch the total amount spent across all projects
$totalSpentQuery = "SELECT COALESCE(SUM(spent_amount), 0) AS total_spent FROM projects";
$totalSpentStmt  = $db->prepare($totalSpentQuery);
$totalSpentStmt->execute();
$totalSpent = $totalSpentStmt->fetch()['total_spent'];

// Fetch the total number of projects
$totalProjectsQuery = "SELECT COUNT(*) AS total_projects FROM projects";
$totalProjectsStmt  = $db->prepare($totalProjectsQuery);
$totalProjectsStmt->execute();
$totalProjects = $totalProjectsStmt->fetch()['total_projects'];

// Fetch the total number of feedback entries
$totalFeedbackQuery = "SELECT COUNT(*) AS total_feedback FROM feedback";
$totalFeedbackStmt  = $db->prepare($totalFeedbackQuery);
$totalFeedbackStmt->execute();
$totalFeedback = $totalFeedbackStmt->fetch()['total_feedback'];

echo json_encode([
    "status"  => "success",
    "message" => "Summary retrieved successfully.",
    "data"    => [
        "total_budget"   => $totalBudget,
        "total_spent"    => $totalSpent,
        "total_projects" => $totalProjects,
        "total_feedback" => $totalFeedback
    ]
]);
?>