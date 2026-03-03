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

// Fetch top 5 most upvoted feedback
if ($isRestricted) {
    $upvotedQuery = "SELECT f.id, f.comment, f.upvotes, f.flag_suspicious, f.status,
                            p.project_name, u.full_name AS citizen_name
                     FROM feedback f
                     JOIN projects p ON f.project_id = p.id
                     JOIN budgets b  ON p.budget_id  = b.id
                     JOIN users u    ON f.citizen_id = u.id
                     WHERE b.barangay_name = :barangay
                     ORDER BY f.upvotes DESC
                     LIMIT 5";
    $upvotedStmt = $db->prepare($upvotedQuery);
    $upvotedStmt->bindParam(":barangay", $_SESSION['barangay']);
} else {
    $upvotedQuery = "SELECT f.id, f.comment, f.upvotes, f.flag_suspicious, f.status,
                            p.project_name, u.full_name AS citizen_name
                     FROM feedback f
                     JOIN projects p ON f.project_id = p.id
                     JOIN users u    ON f.citizen_id = u.id
                     ORDER BY f.upvotes DESC
                     LIMIT 5";
    $upvotedStmt = $db->prepare($upvotedQuery);
}
$upvotedStmt->execute();
$topUpvoted = $upvotedStmt->fetchAll();

// Fetch top 5 most flagged feedback
if ($isRestricted) {
    $flaggedQuery = "SELECT f.id, f.comment, f.upvotes, f.flag_suspicious, f.status,
                            p.project_name, u.full_name AS citizen_name
                     FROM feedback f
                     JOIN projects p ON f.project_id = p.id
                     JOIN budgets b  ON p.budget_id  = b.id
                     JOIN users u    ON f.citizen_id = u.id
                     WHERE f.flag_suspicious = 1
                     AND b.barangay_name = :barangay
                     ORDER BY f.created_at DESC
                     LIMIT 5";
    $flaggedStmt = $db->prepare($flaggedQuery);
    $flaggedStmt->bindParam(":barangay", $_SESSION['barangay']);
} else {
    $flaggedQuery = "SELECT f.id, f.comment, f.upvotes, f.flag_suspicious, f.status,
                            p.project_name, u.full_name AS citizen_name
                     FROM feedback f
                     JOIN projects p ON f.project_id = p.id
                     JOIN users u    ON f.citizen_id = u.id
                     WHERE f.flag_suspicious = 1
                     ORDER BY f.created_at DESC
                     LIMIT 5";
    $flaggedStmt = $db->prepare($flaggedQuery);
}
$flaggedStmt->execute();
$topFlagged = $flaggedStmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Top feedback retrieved successfully.",
    "data"    => [
        "top_upvoted" => $topUpvoted,
        "top_flagged" => $topFlagged
    ]
]);
?>