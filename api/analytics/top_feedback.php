<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch the top 5 most upvoted feedback entries
$topUpvotedQuery = "SELECT f.id, f.comment, f.upvotes, f.flag_suspicious, f.status,
                           p.project_name, u.full_name AS citizen_name
                    FROM feedback f
                    JOIN projects p ON f.project_id = p.id
                    JOIN users u    ON f.citizen_id  = u.id
                    ORDER BY f.upvotes DESC
                    LIMIT 5";
$topUpvotedStmt = $db->prepare($topUpvotedQuery);
$topUpvotedStmt->execute();
$topUpvoted = $topUpvotedStmt->fetchAll();

// Fetch the top 5 most flagged feedback entries
$topFlaggedQuery = "SELECT f.id, f.comment, f.upvotes, f.flag_suspicious, f.status,
                           p.project_name, u.full_name AS citizen_name
                    FROM feedback f
                    JOIN projects p ON f.project_id = p.id
                    JOIN users u    ON f.citizen_id  = u.id
                    WHERE f.flag_suspicious = 1
                    ORDER BY f.created_at DESC
                    LIMIT 5";
$topFlaggedStmt = $db->prepare($topFlaggedQuery);
$topFlaggedStmt->execute();
$topFlagged = $topFlaggedStmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Top feedback retrieved successfully.",
    "data"    => [
        "top_upvoted" => $topUpvoted,
        "top_flagged" => $topFlagged
    ]
]);
?>