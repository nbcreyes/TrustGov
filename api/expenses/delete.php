<?php
/**
 * File:        update.php
 * Description: Updates an existing expense record.
 *              Restricted to official role only.
 *              Also adjusts the project's spent_amount accordingly.
 * Method:      PUT
 * Route:       /api/expenses/update.php
 * Parameters:  id, project_id, description, amount, supplier,
 *              receipt_number, expense_date
 * Returns:     JSON { status, message, data }
 * Author:      TrustGov Dev Team
 * Date:        2025
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

// Only officials can update expense records
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
    empty($data->id)           ||
    empty($data->project_id)   ||
    empty($data->description)  ||
    empty($data->amount)       ||
    empty($data->expense_date)
) {
    echo json_encode([
        "status"  => "error",
        "message" => "ID, project ID, description, amount, and expense date are required.",
        "data"    => null
    ]);
    exit();
}

// Include the database connection
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Fetch the old expense amount to adjust the project's spent_amount
$oldQuery = "SELECT amount, project_id FROM expenses WHERE id = :id LIMIT 1";
$oldStmt  = $db->prepare($oldQuery);
$oldStmt->bindParam(":id", $data->id, PDO::PARAM_INT);
$oldStmt->execute();
$oldExpense = $oldStmt->fetch();

if (!$oldExpense) {
    echo json_encode([
        "status"  => "error",
        "message" => "Expense not found.",
        "data"    => null
    ]);
    exit();
}

// Update the expense record in the database
$query = "UPDATE expenses
          SET project_id     = :project_id,
              description    = :description,
              amount         = :amount,
              supplier       = :supplier,
              receipt_number = :receipt_number,
              expense_date   = :expense_date
          WHERE id = :id";
$stmt = $db->prepare($query);

$stmt->bindParam(":project_id",     $data->project_id,  PDO::PARAM_INT);
$stmt->bindParam(":description",    $data->description);
$stmt->bindParam(":amount",         $data->amount);
$stmt->bindParam(":supplier",       $data->supplier);
$stmt->bindParam(":receipt_number", $data->receipt_number);
$stmt->bindParam(":expense_date",   $data->expense_date);
$stmt->bindParam(":id",             $data->id,          PDO::PARAM_INT);

if ($stmt->execute()) {
    // Recalculate spent_amount on the old project by subtracting old amount
    $revertQuery = "UPDATE projects
                    SET spent_amount = spent_amount - :old_amount
                    WHERE id = :old_project_id";
    $revertStmt = $db->prepare($revertQuery);
    $revertStmt->bindParam(":old_amount",      $oldExpense['amount']);
    $revertStmt->bindParam(":old_project_id",  $oldExpense['project_id'], PDO::PARAM_INT);
    $revertStmt->execute();

    // Add the new amount to the new (or same) project's spent_amount
    $applyQuery = "UPDATE projects
                   SET spent_amount = spent_amount + :new_amount
                   WHERE id = :new_project_id";
    $applyStmt = $db->prepare($applyQuery);
    $applyStmt->bindParam(":new_amount",     $data->amount);
    $applyStmt->bindParam(":new_project_id", $data->project_id, PDO::PARAM_INT);
    $applyStmt->execute();

    echo json_encode([
        "status"  => "success",
        "message" => "Expense updated successfully.",
        "data"    => ["id" => $data->id]
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to update expense. Please try again.",
        "data"    => null
    ]);
}
?>