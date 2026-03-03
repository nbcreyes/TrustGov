<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch the count of projects for each status value
$query = "SELECT status, COUNT(*) AS count
          FROM projects
          GROUP BY status
          ORDER BY FIELD(status, 'planned', 'ongoing', 'completed', 'cancelled')";
$stmt = $db->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode([
    "status"  => "success",
    "message" => "Project status breakdown retrieved successfully.",
    "data"    => $data
]);
?>