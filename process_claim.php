<?php
require 'config.php';
requireLogin();

// Check if user is authorized to process claims
if (!in_array($_SESSION['role'], ['Manager', 'FinanceOfficer', 'Admin'])) {
    header("Location: dashboard.php?error=Unauthorized");
    exit();
}

// Initialize variables
$claim = null;
$category = null;
$department = null;
$employee = null;
$error = null;
$success = null;

// Get claim ID from URL
$claimID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$claimID) {
    header("Location: approvals.php?error=Invalid claim ID");
    exit();
}

try {
    // Fetch claim details
    $stmt = $pdo->prepare("
        SELECT c.*, 
               ec.category_name, 
               d.department_name,
               u.fullName as employee_name,
               u.email as employee_email
        FROM claims c
        LEFT JOIN expense_categories ec ON c.category_id = ec.category_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN users u ON c.userID = u.userID
        WHERE c.claimID = ? AND c.status = 'Submitted'
    ");
    $stmt->execute([$claimID]);
    $claim = $stmt->fetch();
    
    if (!$claim) {
        throw new Exception("Claim not found or it's not in a reviewable state.");
    }
    
    // Check if user can approve this claim
    $canApprove = false;
    
    if ($_SESSION['role'] === 'Manager') {
        // Managers can only approve claims from their department
        $stmt = $pdo->prepare("
            SELECT * FROM departments 
            WHERE department_id = ? AND manager_id = ?
        ");
        $stmt->execute([$claim['department_id'], $_SESSION['user_id']]);
        $canApprove = $stmt->fetch() ? true : false;
    } else {
        // Finance Officers and Admins can approve any claim
        $canApprove = true;
    }
    
    if (!$canApprove) {
        throw new Exception("You don't have permission to process this claim.");
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        $action = $_POST['action'];
        $notes = trim($_POST['notes'] ?? '');
        
        if ($action === 'approve') {
            // Approve claim
            $stmt = $pdo->prepare("
                UPDATE claims 
                SET status = 'Approved', 
                    approverID = ?, 
                    notes = ?, 
                    approval_date = CURRENT_TIMESTAMP, 
                    last_updated = CURRENT_TIMESTAMP
                WHERE claimID = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $claimID]);
            
            // Log the action
            logClaimAudit($claimID, 'approve', "Claim approved. Notes: " . $notes, 'Submitted', 'Approved');
            
            // Send notification to employee
            createNotification(
                $claim['userID'],
                "Claim Approved",
                "Your claim #{$claim['reference_number']} has been approved.",
                'claim_approved',
                $claimID,
                'claim'
            );
            
            $success = "Claim has been approved successfully.";
            
        } elseif ($action === 'reject') {
            if (empty($notes)) {
                throw new Exception("Please provide a reason for rejection.");
            }
            
            // Reject claim
            $stmt = $pdo->prepare("
                UPDATE claims 
                SET status = 'Rejected', 
                    approverID = ?, 
                    rejection_reason = ?, 
                    notes = ?, 
                    last_updated = CURRENT_TIMESTAMP
                WHERE claimID = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $notes, $notes, $claimID]);
            
            // Log the action
            logClaimAudit($claimID, 'reject', "Claim rejected. Reason: " . $notes, 'Submitted', 'Rejected');
            
            // Send notification to employee
            createNotification(
                $claim['userID'],
                "Claim Rejected",
                "Your claim #{$claim['reference_number']} has been rejected. Reason: " . $notes,
                'claim_rejected',
                $claimID,
                'claim'
            );
            
            $success = "Claim has been rejected.";
        }
        
        // Redirect to approvals page after processing
        if ($success) {
            header("Location: approvals.php?success=" . urlencode($success));
            exit();
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Review Claim</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Reference: <?= htmlspecialchars($claim['reference_number'] ?? 'N/A') ?>
            </p>
        </div>
        
        <div>
            <a href="approvals.php" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>
                Back to Approvals
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="alert-circle" class="h-5 w-5 text-red-400 dark:text-red-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200"><?= htmlspecialchars($error) ?></h3>
                </div>
            </div>
        </div>
    <?php elseif ($claim): ?>
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <!-- Claim Details -->
            <div class="px-4 py-5 sm:px-6 bg-blue-600 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium">
                            Status: <?= $claim['status'] ?>
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-blue-100">
                            Submitted on <?= date('F j, Y', strtotime($claim['submission_date'])) ?>
                        </p>
                    </div>
                    <div>
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-white bg-opacity-25 text-white">
                            KSH <?= number_format($claim['amount'], 2) ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200 sm:dark:divide-gray-700">
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Employee</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($claim['employee_name'] ?? 'N/A') ?>
                            <?php if (isset($claim['employee_email'])): ?>
                                <span class="text-gray-500 dark:text-gray-400">(<?= htmlspecialchars($claim['employee_email']) ?>)</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($claim['department_name'] ?? 'N/A') ?>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($claim['category_name'] ?? 'N/A') ?>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <span class="font-medium">KSH <?= number_format($claim['amount'], 2) ?></span>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date Incurred</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= date('F j, Y', strtotime($claim['incurred_date'])) ?>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= nl2br(htmlspecialchars($claim['description'])) ?>
                        </dd>
                    </div>
                    
                    <?php if (!empty($claim['purpose'])): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Business Purpose</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($claim['purpose']) ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Proof of Payment Section -->
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Proof of Payment</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?php if (!empty($claim['receipt_path']) || !empty($claim['mpesa_code'])): ?>
                                <!-- M-PESA Code -->
                                <?php if (!empty($claim['mpesa_code'])): ?>
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">
                                        <i data-lucide="credit-card" class="h-4 w-4 mr-1"></i>
                                        M-PESA Code: <?= htmlspecialchars($claim['mpesa_code']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Receipt -->
                                <?php if (!empty($claim['receipt_path'])): ?>
                                <div class="flex items-center">
                                    <a href="<?= htmlspecialchars($claim['receipt_path']) ?>" target="_blank" class="font-medium text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center">
                                        <i data-lucide="file" class="h-5 w-5 mr-2"></i>
                                        View Receipt
                                        <i data-lucide="external-link" class="h-4 w-4 ml-1"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">
                                    <i data-lucide="alert-triangle" class="h-4 w-4 mr-1"></i>
                                    No payment proof provided
                                </span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <!-- Approval Form -->
            <div class="p-6 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Approve or Reject Claim</h3>
                
                <form action="process_claim.php?id=<?= $claimID ?>" method="post" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Notes <span class="text-red-500 font-normal">(required for rejection)</span>
                        </label>
                        <textarea id="notes" name="notes" rows="3" 
                                 class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-3">
                        <button type="submit" name="action" value="reject" 
                               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <i data-lucide="x" class="h-4 w-4 mr-2"></i>
                            Reject
                        </button>
                        <button type="submit" name="action" value="approve" 
                               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i data-lucide="check" class="h-4 w-4 mr-2"></i>
                            Approve
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize icons
    lucide.createIcons();
});
</script>

<?php include 'includes/footer.php'; ?>