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
$barangay     = $isRestricted ? $_SESSION['barangay'] : null;

// Fetch total allocated budget
if ($isRestricted) {
    $q = "SELECT COALESCE(SUM(total_amount), 0) AS total_budget FROM budgets WHERE barangay_name = :barangay";
    $s = $db->prepare($q);
    $s->bindParam(":barangay", $barangay);
} else {
    $q = "SELECT COALESCE(SUM(total_amount), 0) AS total_budget FROM budgets";
    $s = $db->prepare($q);
}
$s->execute();
$totalBudget = $s->fetch()['total_budget'];

// Fetch total amount spent across projects
if ($isRestricted) {
    $q = "SELECT COALESCE(SUM(p.spent_amount), 0) AS total_spent
          FROM projects p
          JOIN budgets b ON p.budget_id = b.id
          WHERE b.barangay_name = :barangay";
    $s = $db->prepare($q);
    $s->bindParam(":barangay", $barangay);
} else {
    $q = "SELECT COALESCE(SUM(spent_amount), 0) AS total_spent FROM projects";
    $s = $db->prepare($q);
}
$s->execute();
$totalSpent = $s->fetch()['total_spent'];

// Fetch total number of projects
if ($isRestricted) {
    $q = "SELECT COUNT(*) AS total_projects
          FROM projects p
          JOIN budgets b ON p.budget_id = b.id
          WHERE b.barangay_name = :barangay";
    $s = $db->prepare($q);
    $s->bindParam(":barangay", $barangay);
} else {
    $q = "SELECT COUNT(*) AS total_projects FROM projects";
    $s = $db->prepare($q);
}
$s->execute();
$totalProjects = $s->fetch()['total_projects'];

// Fetch total number of feedback entries
if ($isRestricted) {
    $q = "SELECT COUNT(*) AS total_feedback
          FROM feedback f
          JOIN projects p ON f.project_id = p.id
          JOIN budgets b  ON p.budget_id  = b.id
          WHERE b.barangay_name = :barangay";
    $s = $db->prepare($q);
    $s->bindParam(":barangay", $barangay);
} else {
    $q = "SELECT COUNT(*) AS total_feedback FROM feedback";
    $s = $db->prepare($q);
}
$s->execute();
$totalFeedback = $s->fetch()['total_feedback'];

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