/**
 * Log claim audit
 * 
 * @param int $claimID The claim ID
 * @param string $action The action performed (e.g., submit, approve, reject)
 * @param string $details Details about the action
 * @param string $oldStatus Previous status
 * @param string $newStatus New status
 * @return bool True on success, false on failure
 */
function logClaimAudit($claimID, $action, $details, $oldStatus = null, $newStatus = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO claim_audit_logs (
                claimID, userID, action, details, old_status, new_status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
            )
        ");
        
        return $stmt->execute([
            $claimID,
            $_SESSION['user_id'] ?? null,
            $action,
            $details,
            $oldStatus,
            $newStatus
        ]);
    } catch (PDOException $e) {
        error_log("Error logging claim audit: " . $e->getMessage());
        return false;
    }
}

/**
 * Create notification
 * 
 * @param int $userID The user ID to notify
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string $type Notification type
 * @param int $referenceID Related reference ID
 * @param string $referenceType Type of reference (e.g., claim, approval)
 * @return bool True on success, false on failure
 */
function createNotification($userID, $title, $message, $type, $referenceID = null, $referenceType = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                userID, title, message, type, reference_id, reference_type, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP
            )
        ");
        
        return $stmt->execute([
            $userID,
            $title,
            $message,
            $type,
            $referenceID,
            $referenceType
        ]);
    } catch (PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
} 