<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$isRestricted     = isset($_SESSION['role']) && in_array($_SESSION['role'], ['official', 'citizen']);
$hasProjectFilter = !empty($_GET['project_id']);

if ($isRestricted && $hasProjectFilter) {

    // Restricted role + project filter
    $query = "SELECT f.id, f.project_id, f.comment, f.flag_suspicious,
                     f.upvotes, f.status, f.created_at,
                     p.project_name,
                     b.barangay_name,
                     u.full_name AS citizen_name
              FROM feedback f
              JOIN projects p ON f.project_id = p.id
              JOIN budgets b  ON p.budget_id   = b.id
              JOIN users u    ON f.citizen_id  = u.id
              WHERE f.project_id = :project_id
              AND b.barangay_name = :barangay
              ORDER BY f.upvotes DESC, f.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":project_id", $_GET['project_id'], PDO::PARAM_INT);
    $stmt->bindParam(":barangay",   $_SESSION['barangay']);

} elseif ($isRestricted && !$hasProjectFilter) {

    // Restricted role, no project filter — filter by barangay only
    $query = "SELECT f.id, f.project_id, f.comment, f.flag_suspicious,
                     f.upvotes, f.status, f.created_at,
                     p.project_name,
                     b.barangay_name,
                     u.full_name AS citizen_name
              FROM feedback f
              JOIN projects p ON f.project_id = p.id
              JOIN budgets b  ON p.budget_id   = b.id
              JOIN users u    ON f.citizen_id  = u.id
              WHERE b.barangay_name = :barangay
              ORDER BY f.upvotes DESC, f.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":barangay", $_SESSION['barangay']);

} elseif (!$isRestricted && $hasProjectFilter) {

    // Admin + project filter
    $query = "SELECT f.id, f.project_id, f.comment, f.flag_suspicious,
                     f.upvotes, f.status, f.created_at,
                     p.project_name,
                     b.barangay_name,
                     u.full_name AS citizen_name
              FROM feedback f
              JOIN projects p ON f.project_id = p.id
              JOIN budgets b  ON p.budget_id   = b.id
              JOIN users u    ON f.citizen_id  = u.id
              WHERE f.project_id = :project_id
              ORDER BY f.upvotes DESC, f.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":project_id", $_GET['project_id'], PDO::PARAM_INT);

} else {

    // Admin, no filter — fetch everything
    $query = "SELECT f.id, f.project_id, f.comment, f.flag_suspicious,
                     f.upvotes, f.status, f.created_at,
                     p.project_name,
                     b.barangay_name,
                     u.full_name AS citizen_name
              FROM feedback f
              JOIN projects p ON f.project_id = p.id
              JOIN budgets b  ON p.budget_id   = b.id
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