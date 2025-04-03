<?php
require 'config.php';
requireLogin();

// Role check removed - all users have access to reports now

// Initialize variables
$error = null;
$reportType = isset($_GET['type']) ? $_GET['type'] : 'summary';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month
$departmentId = isset($_GET['department_id']) && !empty($_GET['department_id']) ? $_GET['department_id'] : '';
$departments = [];
$reportData = [];
$totalAmount = 0;

try {
    // Get departments for filter
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll();
    
    // Prepare base query parameters
    $queryParams = [$startDate, $endDate];
    $departmentFilter = '';
    
    if (!empty($departmentId)) {
        // Different filtering for different report types
        switch ($reportType) {
            case 'employee':
                $departmentFilter = " AND u.department_id = ?";
                break;
            default:
                $departmentFilter = " AND c.department_id = ?";
                break;
        }
        $queryParams[] = $departmentId;
    }
    
    // Get report data based on selected type
    switch ($reportType) {
        case 'summary':
            // Summary report by department and status
            $query = "SELECT 
                        COALESCE(d.department_name, 'Unassigned') as department_name,
                        c.status,
                        COUNT(c.claimID) as claim_count,
                        SUM(c.amount) as total_amount
                      FROM claims c
                      LEFT JOIN departments d ON c.department_id = d.department_id
                      WHERE c.submission_date BETWEEN ? AND ?
                      $departmentFilter
                      GROUP BY d.department_name, c.status
                      ORDER BY d.department_name, 
                        CASE c.status
                            WHEN 'Submitted' THEN 1
                            WHEN 'Approved' THEN 2
                            WHEN 'Paid' THEN 3
                            WHEN 'Rejected' THEN 4
                            ELSE 5
                        END";
            break;
            
        case 'expense_category':
            // Expense by category
            $query = "SELECT 
                        COALESCE(ec.category_name, 'Uncategorized') as category_name,
                        COUNT(c.claimID) as claim_count,
                        SUM(c.amount) as total_amount
                      FROM claims c
                      LEFT JOIN expense_categories ec ON c.category_id = ec.category_id
                      WHERE c.submission_date BETWEEN ? AND ?
                      $departmentFilter
                      GROUP BY ec.category_name
                      ORDER BY total_amount DESC";
            break;
            
        case 'monthly_trend':
            // Monthly expense trend
            $query = "SELECT 
                        strftime('%Y-%m', c.submission_date) as month,
                        COUNT(c.claimID) as claim_count,
                        SUM(c.amount) as total_amount
                      FROM claims c
                      WHERE c.submission_date BETWEEN ? AND ?
                      $departmentFilter
                      GROUP BY strftime('%Y-%m', c.submission_date)
                      ORDER BY month";
            break;
            
        case 'employee':
            // Expense by employee
            $query = "SELECT 
                        u.fullName as employee_name,
                        COALESCE(d.department_name, 'Unassigned') as department_name,
                        COUNT(c.claimID) as claim_count,
                        SUM(c.amount) as total_amount
                      FROM claims c
                      JOIN users u ON c.userID = u.userID
                      LEFT JOIN departments d ON u.department_id = d.department_id
                      WHERE c.submission_date BETWEEN ? AND ?
                      $departmentFilter
                      GROUP BY u.userID
                      ORDER BY total_amount DESC";
            break;
            
        default:
            $reportType = 'summary';
            $query = "SELECT 
                        COALESCE(d.department_name, 'Unassigned') as department_name,
                        c.status,
                        COUNT(c.claimID) as claim_count,
                        SUM(c.amount) as total_amount
                      FROM claims c
                      LEFT JOIN departments d ON c.department_id = d.department_id
                      WHERE c.submission_date BETWEEN ? AND ?
                      $departmentFilter
                      GROUP BY d.department_name, c.status
                      ORDER BY d.department_name";
    }
    
    // Execute query for report data
    $stmt = $pdo->prepare($query);
    $stmt->execute($queryParams);
    $reportData = $stmt->fetchAll();
    
    // Calculate overall total with the same filters
    $totalQuery = "SELECT COALESCE(SUM(amount), 0) as total FROM claims 
                  WHERE submission_date BETWEEN ? AND ?";
    
    if (!empty($departmentFilter)) {
        $totalQuery .= $departmentFilter;
    }
    
    $stmt = $pdo->prepare($totalQuery);
    $stmt->execute($queryParams);
    $totalAmount = $stmt->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    error_log("Error generating report: " . $e->getMessage() . " - Query: " . (isset($query) ? $query : 'Unknown') . " - Params: " . json_encode($queryParams));
    $error = "An error occurred while generating the report. Please try again later.";
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Financial Reports</h1>
        
        <div>
            <button id="export-csv" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                <i data-lucide="download" class="h-4 w-4 mr-2"></i>
                Export to CSV
            </button>
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
    
    <!-- Report Filters -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <form action="reports.php" method="get" id="report-filters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Report Type</label>
                    <select id="type" name="type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="summary" <?= $reportType === 'summary' ? 'selected' : '' ?>>Department Summary</option>
                        <option value="expense_category" <?= $reportType === 'expense_category' ? 'selected' : '' ?>>Expense Categories</option>
                        <option value="monthly_trend" <?= $reportType === 'monthly_trend' ? 'selected' : '' ?>>Monthly Trend</option>
                        <option value="employee" <?= $reportType === 'employee' ? 'selected' : '' ?>>By Employee</option>
                    </select>
                </div>
                
                <div>
                    <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Department</label>
                    <select id="department_id" name="department_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>" <?= $departmentId == $dept['department_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                </div>
                
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                        <i data-lucide="filter" class="h-4 w-4 mr-2"></i>
                        Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Report Results -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                    <?php
                    switch ($reportType) {
                        case 'summary':
                            echo 'Department Summary';
                            break;
                        case 'expense_category':
                            echo 'Expenses by Category';
                            break;
                        case 'monthly_trend':
                            echo 'Monthly Expense Trend';
                            break;
                        case 'employee':
                            echo 'Expenses by Employee';
                            break;
                    }
                    ?>
                </h2>
                <div class="text-right">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?= date('M d, Y', strtotime($startDate)) ?> - <?= date('M d, Y', strtotime($endDate)) ?>
                    </p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        Total: KSH <?= number_format($totalAmount, 2) ?>
                    </p>
                </div>
            </div>
            
            <?php if (empty($reportData)): ?>
                <div class="text-center py-10">
                    <i data-lucide="bar-chart-2" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600"></i>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No data available</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Try adjusting your filters to see different results.
                    </p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="report-table">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <?php if ($reportType === 'summary'): ?>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Claims</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <?php elseif ($reportType === 'expense_category'): ?>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Category</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Claims</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">% of Total</th>
                                <?php elseif ($reportType === 'monthly_trend'): ?>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Month</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Claims</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <?php elseif ($reportType === 'employee'): ?>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Employee</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Department</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Claims</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($reportData as $row): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <?php if ($reportType === 'summary'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            <?= $row['department_name'] ?: 'Unassigned' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <?= $row['status'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <?= number_format($row['claim_count']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            KSH <?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                    <?php elseif ($reportType === 'expense_category'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            <?= $row['category_name'] ?: 'Uncategorized' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <?= number_format($row['claim_count']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            KSH <?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <?= $totalAmount > 0 ? number_format(($row['total_amount'] / $totalAmount) * 100, 1) . '%' : '0%' ?>
                                        </td>
                                    <?php elseif ($reportType === 'monthly_trend'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            <?= date('F Y', strtotime($row['month'] . '-01')) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <?= number_format($row['claim_count']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            KSH <?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                    <?php elseif ($reportType === 'employee'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            <?= htmlspecialchars($row['employee_name']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <?= $row['department_name'] ?: 'Unassigned' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                            <?= number_format($row['claim_count']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                            KSH <?= number_format($row['total_amount'], 2) ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Chart for visual representation -->
                <div class="mt-8 h-64">
                    <canvas id="reportChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize icons
    lucide.createIcons();
    
    // Auto-submit form when filters change (except date range)
    document.getElementById('type').addEventListener('change', function() {
        document.getElementById('report-filters').submit();
    });
    
    document.getElementById('department_id').addEventListener('change', function() {
        document.getElementById('report-filters').submit();
    });
    
    // CSV Export
    document.getElementById('export-csv').addEventListener('click', function() {
        const table = document.getElementById('report-table');
        
        if (!table) return;
        
        try {
            let csv = [];
            
            // Get header row
            const headerRow = table.querySelectorAll('thead th');
            let header = [];
            headerRow.forEach(cell => {
                header.push('"' + cell.textContent.trim() + '"');
            });
            csv.push(header.join(','));
            
            // Get data rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let rowData = [];
                cells.forEach(cell => {
                    rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
                });
                csv.push(rowData.join(','));
            });
            
            // Create CSV content
            const csvContent = csv.join('\n');
            
            // Create download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            const fileName = 'expense_report_<?= date('Y-m-d') ?>_<?= $reportType ?>.csv';
            
            link.setAttribute('href', url);
            link.setAttribute('download', fileName);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } catch (e) {
            console.error("CSV export failed:", e);
            alert("Failed to export CSV. Please try again.");
        }
    });
    
    // Create chart if data exists
    <?php if (!empty($reportData)): ?>
        const ctx = document.getElementById('reportChart').getContext('2d');
        
        <?php if ($reportType === 'summary'): ?>
            // Transform data for summary chart (by department)
            let departments = {};
            <?php foreach ($reportData as $row): ?>
                if (!departments['<?= $row['department_name'] ?: 'Unassigned' ?>']) {
                    departments['<?= $row['department_name'] ?: 'Unassigned' ?>'] = 0;
                }
                departments['<?= $row['department_name'] ?: 'Unassigned' ?>'] += <?= $row['total_amount'] ?>;
            <?php endforeach; ?>
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(departments),
                    datasets: [{
                        data: Object.values(departments),
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(199, 199, 199, 0.7)',
                            'rgba(83, 102, 255, 0.7)',
                            'rgba(40, 159, 64, 0.7)',
                            'rgba(210, 199, 199, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? 'white' : 'black'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += 'KSH ' + new Intl.NumberFormat().format(context.raw);
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        <?php elseif ($reportType === 'expense_category'): ?>
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [<?php foreach ($reportData as $row): ?>'<?= $row['category_name'] ?: 'Uncategorized' ?>', <?php endforeach; ?>],
                    datasets: [{
                        data: [<?php foreach ($reportData as $row): ?><?= $row['total_amount'] ?>, <?php endforeach; ?>],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(199, 199, 199, 0.7)',
                            'rgba(83, 102, 255, 0.7)',
                            'rgba(40, 159, 64, 0.7)',
                            'rgba(210, 199, 199, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: document.documentElement.classList.contains('dark') ? 'white' : 'black'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += 'KSH ' + new Intl.NumberFormat().format(context.raw);
                                    const percentage = Math.round((context.raw / <?= $totalAmount ?>) * 100);
                                    return label + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        <?php elseif ($reportType === 'monthly_trend'): ?>
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [<?php foreach ($reportData as $row): ?>'<?= date('M Y', strtotime($row['month'] . '-01')) ?>', <?php endforeach; ?>],
                    datasets: [{
                        label: 'Monthly Expenses',
                        data: [<?php foreach ($reportData as $row): ?><?= $row['total_amount'] ?>, <?php endforeach; ?>],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'KSH ' + value.toLocaleString();
                                },
                                color: document.documentElement.classList.contains('dark') ? 'white' : 'black'
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? 'white' : 'black'
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'KSH ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        <?php elseif ($reportType === 'employee'): ?>
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [<?php foreach ($reportData as $row): ?>'<?= $row['employee_name'] ?>', <?php endforeach; ?>],
                    datasets: [{
                        label: 'Employee Expenses',
                        data: [<?php foreach ($reportData as $row): ?><?= $row['total_amount'] ?>, <?php endforeach; ?>],
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'KSH ' + value.toLocaleString();
                                },
                                color: document.documentElement.classList.contains('dark') ? 'white' : 'black'
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        y: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? 'white' : 'black'
                            },
                            grid: {
                                color: document.documentElement.classList.contains('dark') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'KSH ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>