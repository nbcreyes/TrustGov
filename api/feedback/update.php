<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Must be logged in for any update action
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Please log in.",
        "data"    => null
    ]);
    exit();
}

// Read and decode the incoming JSON body
$data = json_decode(file_get_contents("php://input"));

// Validate that id and action are provided
if (empty($data->id) || empty($data->action)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Feedback ID and action are required.",
        "data"    => null
    ]);
    exit();
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if the feedback exists
$checkQuery = "SELECT id FROM feedback WHERE id = :id LIMIT 1";
$checkStmt  = $db->prepare($checkQuery);
$checkStmt->bindParam(":id", $data->id, PDO::PARAM_INT);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Feedback not found.",
        "data"    => null
    ]);
    exit();
}

// Handle the three possible actions
if ($data->action === 'status_update') {

    // Only officials and admins can update feedback status
    if (!in_array($_SESSION['role'], ['official', 'admin'])) {
        echo json_encode([
            "status"  => "error",
            "message" => "Access denied. Officials and admins only.",
            "data"    => null
        ]);
        exit();
    }

    if (empty($data->status)) {
        echo json_encode([
            "status"  => "error",
            "message" => "Status is required for status_update action.",
            "data"    => null
        ]);
        exit();
    }

    // Update the feedback status
    $query = "UPDATE feedback SET status = :status WHERE id = :id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":status", $data->status);
    $stmt->bindParam(":id",     $data->id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        "status"  => "success",
        "message" => "Feedback status updated successfully.",
        "data"    => ["id" => $data->id]
    ]);

} elseif ($data->action === 'upvote') {

    // Increment the upvote count by 1
    $query = "UPDATE feedback SET upvotes = upvotes + 1 WHERE id = :id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":id", $data->id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        "status"  => "success",
        "message" => "Feedback upvoted successfully.",
        "data"    => ["id" => $data->id]
    ]);

} elseif ($data->action === 'flag') {

    // Toggle the flag_suspicious value between 0 and 1
    $query = "UPDATE feedback SET flag_suspicious = 1 WHERE id = :id";
    $stmt  = $db->prepare($query);
    $stmt->bindParam(":id", $data->id, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        "status"  => "success",
        "message" => "Feedback flagged as suspicious.",
        "data"    => ["id" => $data->id]
    ]);

} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid action. Use: status_update, upvote, or flag.",
        "data"    => null
    ]);
}
?>