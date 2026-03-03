<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

session_start();

// Only officials can create budgets
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'official') {
    echo json_encode(["status" => "error", "message" => "Unauthorized. Officials only."]);
    exit;
}

include_once '../config/database.php';
include_once '../audit/log.php';

$database = new Database();
$db = $database->getConnection();

// Parse request body
$data         = json_decode(file_get_contents("php://input"), true);
$barangay_name = isset($data['barangay_name']) ? trim($data['barangay_name']) : '';
$fiscal_year   = isset($data['fiscal_year'])   ? intval($data['fiscal_year']) : 0;
$total_amount  = isset($data['total_amount'])  ? floatval($data['total_amount']) : 0;
$category      = isset($data['category'])      ? trim($data['category'])      : '';
$description   = isset($data['description'])   ? trim($data['description'])   : '';
$posted_by     = $_SESSION['user_id'];

// Validate required fields
if (empty($barangay_name) || !$fiscal_year || !$total_amount || empty($category)) {
    echo json_encode(["status" => "error", "message" => "Barangay name, fiscal year, total amount, and category are required."]);
    exit;
}

// Validate category
$validCategories = ['health', 'infrastructure', 'livelihood', 'admin', 'education', 'others'];
if (!in_array($category, $validCategories)) {
    echo json_encode(["status" => "error", "message" => "Invalid category."]);
    exit;
}

// Validate fiscal year range
if ($fiscal_year < 2000 || $fiscal_year > 2100) {
    echo json_encode(["status" => "error", "message" => "Invalid fiscal year."]);
    exit;
}

// Validate amount
if ($total_amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Total amount must be greater than zero."]);
    exit;
}

// Insert budget
$insertQuery = "INSERT INTO budgets (barangay_name, fiscal_year, total_amount, category, description, posted_by)
                VALUES (:barangay_name, :fiscal_year, :total_amount, :category, :description, :posted_by)";
$insertStmt = $db->prepare($insertQuery);
$insertStmt->bindParam(":barangay_name", $barangay_name, PDO::PARAM_STR);
$insertStmt->bindParam(":fiscal_year",   $fiscal_year,   PDO::PARAM_INT);
$insertStmt->bindParam(":total_amount",  $total_amount);
$insertStmt->bindParam(":category",      $category,      PDO::PARAM_STR);
$insertStmt->bindParam(":description",   $description,   PDO::PARAM_STR);
$insertStmt->bindParam(":posted_by",     $posted_by,     PDO::PARAM_INT);
$insertStmt->execute();

$newId = $db->lastInsertId();

// Log the action to audit trail
logAudit(
    $db,
    $posted_by,
    'CREATE',
    'budgets',
    "Created budget for '{$barangay_name}', category: {$category}, amount: ₱" . number_format($total_amount, 2) . ", fiscal year: {$fiscal_year}."
);

echo json_encode([
    "status"  => "success",
    "message" => "Budget created successfully.",
    "data"    => ["id" => $newId]
]);
?>