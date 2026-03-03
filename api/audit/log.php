<?php
/**
 * Inserts a record into the audit_logs table.
 *
 * @param PDO    $db          Active PDO connection
 * @param int    $user_id     ID of the user performing the action
 * @param string $action      Action type: CREATE | UPDATE | DELETE
 * @param string $module      Module name: budgets | projects | expenses | feedback | users
 * @param string $description Human-readable description of the action
 */
function logAudit($db, $user_id, $action, $module, $description) {
    try {
        $query = "INSERT INTO audit_logs (user_id, action, module, description)
                  VALUES (:user_id, :action, :module, :description)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id",     $user_id,     PDO::PARAM_INT);
        $stmt->bindParam(":action",      $action,      PDO::PARAM_STR);
        $stmt->bindParam(":module",      $module,      PDO::PARAM_STR);
        $stmt->bindParam(":description", $description, PDO::PARAM_STR);
        $stmt->execute();
    } catch (Exception $e) {
        // Audit logging should never break the main request
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Inserts a notification for every official in a given barangay.
 *
 * @param PDO    $db        Active PDO connection
 * @param string $barangay  Barangay name to target
 * @param string $message   Notification message text
 */
function notifyOfficials($db, $barangay, $message) {
    try {
        // Find all officials in the same barangay
        $query = "SELECT id FROM users
                  WHERE role = 'official'
                  AND barangay = :barangay";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":barangay", $barangay, PDO::PARAM_STR);
        $stmt->execute();
        $officials = $stmt->fetchAll();

        if (empty($officials)) return;

        // Insert one notification per official
        $insertQuery = "INSERT INTO notifications (user_id, message)
                        VALUES (:user_id, :message)";
        $insertStmt = $db->prepare($insertQuery);

        foreach ($officials as $official) {
            $insertStmt->bindParam(":user_id", $official['id'], PDO::PARAM_INT);
            $insertStmt->bindParam(":message", $message,        PDO::PARAM_STR);
            $insertStmt->execute();
        }
    } catch (Exception $e) {
        // Notifications should never break the main request
        error_log("Notification error: " . $e->getMessage());
    }
}
?>