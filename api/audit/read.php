<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([
        "status"  => "error",
        "message" => "Unauthorized. Admin access required.",
        "data"    => []
    ]);
    exit;
}

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT
              a.id,
              a.action,
              a.module,
              a.description,
              a.created_at,
              u.full_name,
              u.role,
              u.barangay
          FROM audit_logs a
          JOIN users u ON a.user_id = u.id
          ORDER BY a.created_at DESC
          LIMIT 200";

$stmt = $db->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll();

if ($data) {
    echo json_encode([
        "status"  => "success",
        "message" => "Audit logs retrieved successfully.",
        "data"    => $data
    ]);
} else {
    echo json_encode([
        "status"  => "success",
        "message" => "No audit logs found.",
        "data"    => []
    ]);
}
?>