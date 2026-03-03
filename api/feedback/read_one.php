<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Validate that the id parameter is provided
if (empty($_GET['id'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Feedback ID is required.",
        "data"    => null
    ]);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch a single feedback record by ID with project and citizen details
$query = "SELECT f.id, f.comment, f.flag_suspicious, f.upvotes, f.status, f.created_at,
                 p.id AS project_id, p.project_name,
                 u.id AS citizen_id, u.full_name AS citizen_name
          FROM feedback f
          JOIN projects p ON f.project_id = p.id
          JOIN users u    ON f.citizen_id  = u.id
          WHERE f.id = :id
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bindParam(":id", $_GET['id'], PDO::PARAM_INT);
$stmt->execute();

$feedback = $stmt->fetch();

if ($feedback) {
    echo json_encode([
        "status"  => "success",
        "message" => "Feedback retrieved successfully.",
        "data"    => $feedback
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Feedback not found.",
        "data"    => null
    ]);
}
?>