<?php
require 'config.php';
requireLogin();

// Initialize variables
$claims = [];
$error = null;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;
$totalClaims = 0;

try {
    // Prepare the base query
    $queryBase = "FROM claims c 
                  LEFT JOIN expense_categories ec ON c.category_id = ec.category_id 
                  WHERE c.userID = ?";
    
    // Apply status filter if not 'all'
    $queryParams = [$_SESSION['user_id']];
    if ($statusFilter !== 'all') {
        $queryBase .= " AND c.status = ?";
        $queryParams[] = $statusFilter;
    }
    
    // Count total claims for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) " . $queryBase);
    $countStmt->execute($queryParams);
    $totalClaims = $countStmt->fetchColumn();
    $totalPages = ceil($totalClaims / $perPage);
    
    // Fetch claims with pagination
    $queryFull = "SELECT c.*, ec.category_name " . $queryBase . " 
                  ORDER BY c.submission_date DESC 
                  LIMIT ? OFFSET ?";
    $queryParams[] = $perPage;
    $queryParams[] = $offset;
    
    $stmt = $pdo->prepare($queryFull);
    $stmt->execute($queryParams);
    $claims = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching claims: " . $e->getMessage());
    $error = "An error occurred while retrieving your claims. Please try again later.";
}

// Function to get status badge class
function getStatusClass($status) {
    switch ($status) {
        case 'Approved':
            return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
        case 'Paid':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100';
        case 'Rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
        case 'Submitted':
        case 'Under Review':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
        case 'Draft':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Claims</h1>
        
        <div class="flex flex-col sm:flex-row gap-4">
            <!-- Status Filter -->
            <div class="inline-flex items-center">
                <label for="status-filter" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300">Status:</label>
                <select id="status-filter" class="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
                    <option value="Draft" <?= $statusFilter === 'Draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="Submitted" <?= $statusFilter === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="Paid" <?= $statusFilter === 'Paid' ? 'selected' : '' ?>>Paid</option>
                </select>
            </div>
            
            <!-- New Claim Button -->
            <a href="submit_claim.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                New Claim
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
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200"><?= $error ?></h3>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Claims Table -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <?php if (empty($claims)): ?>
            <div class="text-center py-16">
                <i data-lucide="file-text" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600"></i>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No claims found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <?php if ($statusFilter !== 'all'): ?>
                        You don't have any claims with the selected status.
                    <?php else: ?>
                        You haven't submitted any claims yet.
                    <?php endif; ?>
                </p>
                <div class="mt-6">
                    <a href="submit_claim.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                        <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
                        Submit Your First Claim
                    </a>
                </div>
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
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Amount
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($claims as $claim): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($claim['reference_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <?= date('M d, Y', strtotime($claim['submission_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <?= htmlspecialchars($claim['category_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    KSH <?= number_format($claim['amount'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getStatusClass($claim['status']) ?>">
                                        <?= $claim['status'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <a href="view_claim.php?id=<?= $claim['claimID'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline">View</a>
                                    <?php if ($claim['status'] === 'Draft'): ?>
                                        | <a href="edit_claim.php?id=<?= $claim['claimID'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline">Edit</a>
                                    <?php endif; ?>
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
                                Showing <span class="font-medium"><?= $offset + 1 ?></span> to <span class="font-medium"><?= min($offset + $perPage, $totalClaims) ?></span> of <span class="font-medium"><?= $totalClaims ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>&status=<?= $statusFilter ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <span class="sr-only">Previous</span>
                                        <i data-lucide="chevron-left" class="h-5 w-5"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <a href="?page=<?= $i ?>&status=<?= $statusFilter ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium <?= $i === $page ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?= $page + 1 ?>&status=<?= $statusFilter ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
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
    // Handle status filter change
    const statusFilter = document.getElementById('status-filter');
    statusFilter.addEventListener('change', function() {
        window.location.href = 'view_claims.php?status=' + this.value;
    });
    
    // Initialize icons
    lucide.createIcons();
});
</script>

<?php include 'includes/footer.php'; ?> 