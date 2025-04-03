<?php
require 'config.php';
requireLogin();

// Initialize variables
$claim = null;
$category = null;
$department = null;
$employee = null;
$approver = null;
$error = null;
$success = null;
$can_approve = false;

// Get claim ID from URL
$claimID = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$claimID) {
    header("Location: view_claims.php?error=Invalid claim ID");
    exit();
}

try {
    // Check if user can access this claim
    if ($_SESSION['role'] === 'Employee') {
        // Employees can only view their own claims
        $stmt = $pdo->prepare("SELECT * FROM claims WHERE claimID = ? AND userID = ?");
        $stmt->execute([$claimID, $_SESSION['user_id']]);
    } else {
        // Managers, Finance Officers, and Admins can view all claims
        $stmt = $pdo->prepare("SELECT * FROM claims WHERE claimID = ?");
        $stmt->execute([$claimID]);
    }
    
    $claim = $stmt->fetch();
    
    if (!$claim) {
        throw new Exception("Claim not found or you don't have permission to view it.");
    }
    
    // Fetch related data
    // Get category
    $stmt = $pdo->prepare("SELECT * FROM expense_categories WHERE category_id = ?");
    $stmt->execute([$claim['category_id']]);
    $category = $stmt->fetch();
    
    // Get department
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE department_id = ?");
    $stmt->execute([$claim['department_id']]);
    $department = $stmt->fetch();
    
    // Get employee (claim submitter)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
    $stmt->execute([$claim['userID']]);
    $employee = $stmt->fetch();
    
    // Get approver if claim is approved
    if ($claim['approverID']) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
        $stmt->execute([$claim['approverID']]);
        $approver = $stmt->fetch();
    }
    
    // Check if current user can approve/reject this claim
    if (in_array($_SESSION['role'], ['Manager', 'FinanceOfficer', 'Admin']) && $claim['status'] === 'Submitted') {
        if ($_SESSION['role'] === 'Manager') {
            // Managers can only approve claims from their department
            $stmt = $pdo->prepare("SELECT * FROM departments WHERE department_id = ? AND manager_id = ?");
            $stmt->execute([$claim['department_id'], $_SESSION['user_id']]);
            $can_approve = $stmt->fetch() ? true : false;
        } else {
            // Finance Officers and Admins can approve any claim
            $can_approve = true;
        }
    }
    
    // Handle form submission (approve/reject)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_approve) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        $action = $_POST['action'];
        $notes = trim($_POST['notes'] ?? '');
        
        if ($action === 'approve') {
            // Approve claim
            $stmt = $pdo->prepare("UPDATE claims SET status = 'Approved', approverID = ?, notes = ?, approval_date = CURRENT_TIMESTAMP, last_updated = CURRENT_TIMESTAMP WHERE claimID = ?");
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
            $stmt = $pdo->prepare("UPDATE claims SET status = 'Rejected', approverID = ?, rejection_reason = ?, notes = ?, last_updated = CURRENT_TIMESTAMP WHERE claimID = ?");
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
            
        } elseif ($action === 'mark_paid') {
            // Mark claim as paid
            $payment_reference = trim($_POST['payment_reference'] ?? '');
            
            $stmt = $pdo->prepare("UPDATE claims SET status = 'Paid', payment_reference = ?, payment_date = CURRENT_TIMESTAMP, last_updated = CURRENT_TIMESTAMP WHERE claimID = ?");
            $stmt->execute([$payment_reference, $claimID]);
            
            // Log the action
            logClaimAudit($claimID, 'mark_paid', "Claim marked as paid. Payment reference: " . $payment_reference, 'Approved', 'Paid');
            
            // Send notification to employee
            createNotification(
                $claim['userID'],
                "Claim Paid",
                "Your claim #{$claim['reference_number']} has been processed for payment.",
                'claim_paid',
                $claimID,
                'claim'
            );
            
            $success = "Claim has been marked as paid.";
        }
        
        // Reload claim data after update
        $stmt = $pdo->prepare("SELECT * FROM claims WHERE claimID = ?");
        $stmt->execute([$claimID]);
        $claim = $stmt->fetch();
        
        // Reload approver data if needed
        if ($claim['approverID']) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
            $stmt->execute([$claim['approverID']]);
            $approver = $stmt->fetch();
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
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Claim Details</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Reference: <?= htmlspecialchars($claim['reference_number'] ?? 'N/A') ?>
            </p>
        </div>
        
        <div>
            <a href="<?= $_SESSION['role'] === 'Employee' ? 'view_claims.php' : 'approvals.php' ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <i data-lucide="arrow-left" class="h-4 w-4 mr-2"></i>
                Back to <?= $_SESSION['role'] === 'Employee' ? 'My Claims' : 'Approvals' ?>
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
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="check-circle" class="h-5 w-5 text-green-400 dark:text-green-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800 dark:text-green-200"><?= htmlspecialchars($success) ?></h3>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($claim): ?>
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <!-- Status Banner -->
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 <?= getStatusBannerClass($claim['status']) ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-white">
                            Status: <?= $claim['status'] ?>
                        </h3>
                        <p class="mt-1 max-w-2xl text-sm text-gray-100">
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
            
            <!-- Claim Details -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200 sm:dark:divide-gray-700">
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Employee</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($employee['fullName'] ?? 'N/A') ?>
                            <?php if ($employee): ?>
                                <span class="text-gray-500 dark:text-gray-400">(<?= htmlspecialchars($employee['email']) ?>)</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Department</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($department['department_name'] ?? 'N/A') ?>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($category['category_name'] ?? 'N/A') ?>
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
                    
                    <?php if (in_array($claim['status'], ['Approved', 'Rejected', 'Paid']) && $approver): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            <?= $claim['status'] === 'Rejected' ? 'Rejected' : 'Approved' ?> By
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <?= htmlspecialchars($approver['fullName']) ?> (<?= htmlspecialchars($approver['role']) ?>)
                            <?php if ($claim['approval_date']): ?>
                                <span class="text-gray-500 dark:text-gray-400">on <?= date('F j, Y', strtotime($claim['approval_date'])) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($claim['status'] === 'Rejected' && !empty($claim['rejection_reason'])): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Rejection Reason</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4">
                                <?= nl2br(htmlspecialchars($claim['rejection_reason'])) ?>
                            </div>
                        </dd>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($claim['status'] === 'Paid' && !empty($claim['payment_reference'])): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Reference</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-sm font-medium bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">
                                <?= htmlspecialchars($claim['payment_reference']) ?>
                            </span>
                            <?php if ($claim['payment_date']): ?>
                                <span class="text-gray-500 dark:text-gray-400 ml-2">on <?= date('F j, Y', strtotime($claim['payment_date'])) ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
            
            <!-- Approval Actions -->
            <?php if ($can_approve): ?>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Approve or Reject Claim</h3>
                
                <form action="view_claim.php?id=<?= $claimID ?>" method="post" class="space-y-4">
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
            <?php endif; ?>
            
            <!-- Mark as Paid (for Finance Officer and Admin only) -->
            <?php if (in_array($_SESSION['role'], ['FinanceOfficer', 'Admin']) && $claim['status'] === 'Approved'): ?>
            <div class="p-6 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Mark as Paid</h3>
                
                <form action="view_claim.php?id=<?= $claimID ?>" method="post" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div>
                        <label for="payment_reference" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Payment Reference
                        </label>
                        <input type="text" id="payment_reference" name="payment_reference" 
                               class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Reference number for the payment (e.g., bank transfer ID, check number)</p>
                    </div>
                    
                    <div class="flex items-center justify-end">
                        <button type="submit" name="action" value="mark_paid" 
                               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i data-lucide="credit-card" class="h-4 w-4 mr-2"></i>
                            Mark as Paid
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize icons
    lucide.createIcons();
});

<?php
// Helper function to get status banner background color
function getStatusBannerClass($status) {
    switch ($status) {
        case 'Draft':
            return 'bg-gray-600';
        case 'Submitted':
            return 'bg-blue-600';
        case 'Approved':
            return 'bg-green-600';
        case 'Rejected':
            return 'bg-red-600';
        case 'Paid':
            return 'bg-purple-600';
        default:
            return 'bg-gray-600';
    }
}
?>
</script>

<?php include 'includes/footer.php'; ?> 