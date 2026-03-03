<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if filtering by project_id is requested
if (!empty($_GET['project_id'])) {

    // Fetch all feedback for a specific project
    $query = "SELECT f.id, f.comment, f.flag_suspicious, f.upvotes, f.status, f.created_at,
                     p.project_name,
                     u.full_name AS citizen_name
              FROM feedback f
              JOIN projects p ON f.project_id = p.id
              JOIN users u    ON f.citizen_id  = u.id
              WHERE f.project_id = :project_id
              ORDER BY f.upvotes DESC, f.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":project_id", $_GET['project_id'], PDO::PARAM_INT);

} else {

    // Fetch all feedback regardless of project
    $query = "SELECT f.id, f.comment, f.flag_suspicious, f.upvotes, f.status, f.created_at,
                     p.project_name,
                     u.full_name AS citizen_name
              FROM feedback f
              JOIN projects p ON f.project_id = p.id
              JOIN users u    ON f.citizen_id  = u.id
              ORDER BY f.upvotes DESC, f.created_at DESC";
    $stmt = $db->prepare($query);

}

$stmt->execute();
$feedback = $stmt->fetchAll();

if ($feedback) {
    echo json_encode([
        "status"  => "success",
        "message" => "Feedback retrieved successfully.",
        "data"    => $feedback
    ]);
} else {
    echo json_encode([
        "status"  => "success",
        "message" => "No feedback found.",
        "data"    => []
    ]);
}
?>