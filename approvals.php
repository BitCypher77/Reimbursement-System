<?php
require 'config.php';
requireLogin();

// Only managers, finance officers, and admins can access this page
if (!in_array($_SESSION['role'], ['Manager', 'FinanceOfficer', 'Admin'])) {
    header("Location: dashboard.php?error=You don't have permission to access this page");
    exit();
}

// Initialize variables
$pendingApprovals = [];
$error = null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$totalApprovals = 0;

try {
    // Prepare the base query for managers and finance officers
    if ($_SESSION['role'] === 'Manager') {
        $queryBase = "FROM claims c
                     JOIN users u ON c.userID = u.userID
                     LEFT JOIN departments d ON c.department_id = d.department_id
                     LEFT JOIN expense_categories ec ON c.category_id = ec.category_id
                     WHERE c.status = 'Submitted'
                     AND ? IN (SELECT manager_id FROM departments WHERE department_id = c.department_id)";
        $queryParams = [$_SESSION['user_id']];
    } else if ($_SESSION['role'] === 'FinanceOfficer') {
        $queryBase = "FROM claims c
                     JOIN users u ON c.userID = u.userID
                     LEFT JOIN departments d ON c.department_id = d.department_id
                     LEFT JOIN expense_categories ec ON c.category_id = ec.category_id
                     WHERE c.status = 'Submitted'";
        $queryParams = [];
    } else { // Admin
        $queryBase = "FROM claims c
                     JOIN users u ON c.userID = u.userID
                     LEFT JOIN departments d ON c.department_id = d.department_id
                     LEFT JOIN expense_categories ec ON c.category_id = ec.category_id
                     WHERE c.status = 'Submitted'";
        $queryParams = [];
    }
    
    // Count total approvals for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) " . $queryBase);
    $countStmt->execute($queryParams);
    $totalApprovals = $countStmt->fetchColumn();
    $totalPages = ceil($totalApprovals / $perPage);
    
    // Fetch pending approvals with pagination
    $queryFull = "SELECT c.claimID, c.reference_number, c.amount, c.currency, c.submission_date,
                  u.fullName as employee_name, u.email as employee_email, 
                  d.department_name, ec.category_name " . $queryBase . " 
                  ORDER BY c.submission_date ASC 
                  LIMIT ? OFFSET ?";
    $queryParams[] = $perPage;
    $queryParams[] = $offset;
    
    $stmt = $pdo->prepare($queryFull);
    $stmt->execute($queryParams);
    $pendingApprovals = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching pending approvals: " . $e->getMessage());
    $error = "An error occurred while retrieving pending approvals. Please try again later.";
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pending Approvals</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="alert-circle" class="h-5 w-5 text-red-400 dark:text-red-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200"><?= $error ?></h3>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Pending Approvals Table -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
        <?php if (empty($pendingApprovals)): ?>
            <div class="text-center py-16">
                <i data-lucide="check-circle" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600"></i>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No pending approvals</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    You don't have any claims waiting for your approval.
                </p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Reference
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Employee
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Department
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Amount
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Submitted
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($pendingApprovals as $approval): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($approval['reference_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <?= htmlspecialchars($approval['employee_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <?= htmlspecialchars($approval['department_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <?= htmlspecialchars($approval['category_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    KSH <?= number_format($approval['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <?= date('M d, Y', strtotime($approval['submission_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <a href="process_claim.php?id=<?= $approval['claimID'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700 sm:px-6">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $perPage, $totalApprovals) ?></span> of <span class="font-medium"><?= $totalApprovals ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <span class="sr-only">Previous</span>
                                        <i data-lucide="chevron-left" class="h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?page=<?= $i ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium <?= $i === $page ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <span class="sr-only">Next</span>
                                        <i data-lucide="chevron-right" class="h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize icons
    lucide.createIcons();
});
</script>

<?php include 'includes/footer.php'; ?> 