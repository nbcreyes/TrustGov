<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only officials can update budget entries
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
    empty($data->id)            ||
    empty($data->barangay_name) ||
    empty($data->fiscal_year)   ||
    empty($data->total_amount)  ||
    empty($data->category)
) {
    echo json_encode([
        "status"  => "error",
        "message" => "ID, barangay name, fiscal year, total amount, and category are required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if the budget exists before updating
$checkQuery = "SELECT id FROM budgets WHERE id = :id LIMIT 1";
$checkStmt  = $db->prepare($checkQuery);
$checkStmt->bindParam(":id", $data->id, PDO::PARAM_INT);
$checkStmt->execute();

if ($checkStmt->rowCount() === 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Budget not found.",
        "data"    => null
    ]);
    exit();
}

// Update the budget record in the database
$query = "UPDATE budgets
          SET barangay_name = :barangay_name,
              fiscal_year   = :fiscal_year,
              total_amount  = :total_amount,
              category      = :category,
              description   = :description
          WHERE id = :id";
$stmt = $db->prepare($query);

$stmt->bindParam(":barangay_name", $data->barangay_name);
$stmt->bindParam(":fiscal_year",   $data->fiscal_year);
$stmt->bindParam(":total_amount",  $data->total_amount);
$stmt->bindParam(":category",      $data->category);
$stmt->bindParam(":description",   $data->description);
$stmt->bindParam(":id",            $data->id, PDO::PARAM_INT);

if ($stmt->execute()) {
    echo json_encode([
        "status"  => "success",
        "message" => "Budget updated successfully.",
        "data"    => ["id" => $data->id]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to update budget. Please try again.",
        "data"    => null
    ]);
}
?>