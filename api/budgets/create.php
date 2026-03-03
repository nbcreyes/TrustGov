<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only officials can create budget entries
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'official') {
    echo json_encode([
        "status"  => "error",
        "message" => "Access denied. Officials only.",
        "data"    => null
    ]);
    exit();
}

// Read and decode the incoming JSON body
$data = json_decode(file_get_contents("php://input"));

// Validate that all required fields are present
if (
    empty($data->barangay_name) ||
    empty($data->fiscal_year)   ||
    empty($data->total_amount)  ||
    empty($data->category)
) {
    echo json_encode([
        "status"  => "error",
        "message" => "Barangay name, fiscal year, total amount, and category are required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Insert the new budget record into the database
$query = "INSERT INTO budgets (barangay_name, fiscal_year, total_amount, category, description, posted_by)
          VALUES (:barangay_name, :fiscal_year, :total_amount, :category, :description, :posted_by)";
$stmt = $db->prepare($query);

$stmt->bindParam(":barangay_name", $data->barangay_name);
$stmt->bindParam(":fiscal_year",   $data->fiscal_year);
$stmt->bindParam(":total_amount",  $data->total_amount);
$stmt->bindParam(":category",      $data->category);
$stmt->bindParam(":description",   $data->description);
$stmt->bindParam(":posted_by",     $_SESSION['user_id'], PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Budget created successfully.",
        "data"    => ["id" => $db->lastInsertId()]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to create budget. Please try again.",
        "data"    => null
    ]);
}
?>