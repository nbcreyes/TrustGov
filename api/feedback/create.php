<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

// Only citizens can submit feedback
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'citizen') {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Citizens only."]);
    exit;
}

include_once '../config/database.php';
include_once '../audit/log.php';

$database = new Database();
$db = $database->getConnection();

// Parse request body
$data       = json_decode(file_get_contents("php://input"), true);
$project_id = isset($data['project_id']) ? intval($data['project_id'])      : 0;
$comment    = isset($data['comment'])    ? trim($data['comment'])            : '';
$citizen_id = $_SESSION['user_id'];

// Validate required fields
if (!$project_id || empty($comment)) {
    echo json_encode(["status" => "error", "message" => "Project and comment are required."]);
    exit;
}

// Verify the project exists
$checkQuery = "SELECT p.id, p.project_name, b.barangay_name
               FROM projects p
               JOIN budgets b ON p.budget_id = b.id
               WHERE p.id = :project_id
               LIMIT 1";
$checkStmt = $db->prepare($checkQuery);
$checkStmt->bindParam(":project_id", $project_id, PDO::PARAM_INT);
$checkStmt->execute();
$project = $checkStmt->fetch();

if (!$project) {
    echo json_encode(["status" => "error", "message" => "Project not found."]);
    exit;
}

// Insert feedback
$insertQuery = "INSERT INTO feedback (project_id, citizen_id, comment)
                VALUES (:project_id, :citizen_id, :comment)";
$insertStmt = $db->prepare($insertQuery);
$insertStmt->bindParam(":project_id", $project_id, PDO::PARAM_INT);
$insertStmt->bindParam(":citizen_id", $citizen_id, PDO::PARAM_INT);
$insertStmt->bindParam(":comment",    $comment,    PDO::PARAM_STR);
$insertStmt->execute();

$newId = $db->lastInsertId();

// Log the action to audit trail
logAudit(
    $db,
    $citizen_id,
    'CREATE',
    'feedback',
    "Submitted feedback on project '{$project['project_name']}' (ID: {$project_id})."
);

// Notify all officials in the same barangay
notifyOfficials(
    $db,
    $project['barangay_name'],
    "New citizen feedback submitted on project '{$project['project_name']}'. Please review."
);

echo json_encode([
    "status"  => "success",
    "message" => "Feedback submitted successfully.",
    "data"    => ["id" => $newId]
]);
?>