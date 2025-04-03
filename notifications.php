<?php
require 'config.php';
requireLogin();

// Initialize variables
$notifications = [];
$unreadCount = 0;

try {
    // Check if notifications table exists
    $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='notifications'");
    if ($tableCheck->fetchColumn()) {
        // Fetch notifications for the user
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE recipient_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$_SESSION['user_id']]);
        $notifications = $stmt->fetchAll();
        
        // Count unread notifications
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION['user_id']]);
        $unreadCount = $stmt->fetchColumn();
        
        // Mark all as read if requested
        if (isset($_GET['mark_all_read'])) {
            try {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE recipient_id = ? AND is_read = 0");
                $result = $stmt->execute([$_SESSION['user_id']]);
                
                if ($result) {
                    $rowCount = $stmt->rowCount();
                    if ($rowCount > 0) {
                        $_SESSION['flash_message'] = [
                            'type' => 'success',
                            'message' => $rowCount . ' notification(s) marked as read.'
                        ];
                    } else {
                        $_SESSION['flash_message'] = [
                            'type' => 'info',
                            'message' => 'No unread notifications to mark as read.'
                        ];
                    }
                } else {
                    $_SESSION['flash_message'] = [
                        'type' => 'warning',
                        'message' => 'Could not mark notifications as read.'
                    ];
                }
            } catch (Exception $e) {
                error_log("Error marking all notifications as read: " . $e->getMessage());
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'message' => 'An error occurred while marking notifications as read.'
                ];
            }
            
            // Redirect to avoid resubmission
            header("Location: notifications.php");
            exit();
        }
        
        // Mark single notification as read
        if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
            try {
                $notification_id = (int)$_GET['mark_read'];
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND recipient_id = ?");
                $result = $stmt->execute([$notification_id, $_SESSION['user_id']]);
                
                if ($result && $stmt->rowCount() > 0) {
                    $_SESSION['flash_message'] = [
                        'type' => 'success',
                        'message' => 'Notification marked as read.'
                    ];
                } else {
                    $_SESSION['flash_message'] = [
                        'type' => 'warning',
                        'message' => 'Could not mark notification as read. It may not exist or already be marked as read.'
                    ];
                }
            } catch (Exception $e) {
                error_log("Error marking notification as read: " . $e->getMessage());
                $_SESSION['flash_message'] = [
                    'type' => 'error',
                    'message' => 'An error occurred while marking the notification as read.'
                ];
            }
            
            // Redirect to avoid resubmission
            header("Location: notifications.php");
            exit();
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching notifications: " . $e->getMessage());
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>
        
        <?php if ($unreadCount > 0): ?>
        <a href="notifications.php?mark_all_read=1" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Mark all as read
        </a>
        <?php endif; ?>
    </div>
    
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
        <?php if (empty($notifications)): ?>
            <div class="p-8 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900">
                    <i data-lucide="bell" class="h-6 w-6 text-blue-600 dark:text-blue-400"></i>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No notifications</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    You don't have any notifications at the moment.
                </p>
            </div>
        <?php else: ?>
            <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                <?php foreach ($notifications as $notification): ?>
                    <li class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 <?= $notification['is_read'] ? '' : 'bg-blue-50 dark:bg-blue-900/10' ?>">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <?php if ($notification['notification_type'] === 'claim_status'): ?>
                                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900">
                                        <i data-lucide="file-text" class="h-5 w-5 text-blue-600 dark:text-blue-400"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-gray-100 dark:bg-gray-800">
                                        <i data-lucide="bell" class="h-5 w-5 text-gray-600 dark:text-gray-400"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($notification['title']) ?>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <?= htmlspecialchars($notification['message']) ?>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    <?= date('M j, Y \a\t g:i a', strtotime($notification['created_at'])) ?>
                                </p>
                            </div>
                            <div>
                                <?php if (!$notification['is_read']): ?>
                                    <button data-notification-id="<?= $notification['notification_id'] ?>" 
                                           class="mark-read-btn inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Mark as read
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Initialize Lucide Icons
    lucide.createIcons();
    
    // Handle mark as read functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Get all mark as read buttons
        const markReadButtons = document.querySelectorAll('.mark-read-btn');
        
        // Add click event listener to each button
        markReadButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const notificationId = this.getAttribute('data-notification-id');
                const listItem = this.closest('li');
                const button = this;
                
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = 'notifications.php';
                form.style.display = 'none';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'mark_read';
                input.value = notificationId;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            });
        });
    });
</script> 