<?php
require 'config.php';
requireLogin();

// Initialize variables
$messages = [];
$unreadCount = 0;
$success = false;
$error = null;

// Get users who can be messaged (now including all users)
$recipients = [];
try {
    $stmt = $pdo->prepare("SELECT userID, fullName, role FROM users WHERE is_active = 1 ORDER BY fullName");
    $stmt->execute();
    $recipients = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching recipients: " . $e->getMessage());
}

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        // Validate input
        $recipient_id = filter_input(INPUT_POST, 'recipient_id', FILTER_VALIDATE_INT);
        if (!$recipient_id) {
            throw new Exception("Please select a valid recipient.");
        }
        
        $subject = trim($_POST['subject']);
        if (empty($subject)) {
            throw new Exception("Please enter a subject.");
        }
        
        $message_text = trim($_POST['message_text']);
        if (empty($message_text)) {
            throw new Exception("Please enter a message.");
        }
        
        // Insert the message
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, subject, message_text) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $recipient_id, $subject, $message_text]);
        
        // Create notification for recipient
        $stmt = $pdo->prepare("SELECT fullName FROM users WHERE userID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $sender = $stmt->fetch();
        
        createNotification(
            $recipient_id, 
            "New Message", 
            "You have received a new message from " . $sender['fullName'] . ": " . $subject, 
            "new_message", 
            $pdo->lastInsertId(), 
            "message"
        );
        
        $success = "Your message has been sent successfully.";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle marking a message as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    try {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE message_id = ? AND (sender_id = ? OR recipient_id = ?)");
        $stmt->execute([$_GET['mark_read'], $_SESSION['user_id'], $_SESSION['user_id']]);
        header("Location: messages.php?success=Message marked as read");
        exit();
    } catch (PDOException $e) {
        error_log("Error marking message as read: " . $e->getMessage());
        $error = "Could not mark message as read. Please try again.";
    }
}

// Fetch messages
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               s.fullName as sender_name, s.role as sender_role, 
               r.fullName as recipient_name, r.role as recipient_role
        FROM messages m
        JOIN users s ON m.sender_id = s.userID
        JOIN users r ON m.recipient_id = r.userID
        WHERE m.sender_id = ? OR m.recipient_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $messages = $stmt->fetchAll();
    
    // Count unread messages
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    $error = "Could not retrieve messages. Please try again later.";
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Messages</h1>
        
        <button id="new-message-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i data-lucide="plus" class="h-4 w-4 mr-2"></i>
            New Message
        </button>
    </div>
    
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
    
    <!-- New Message Form -->
    <div id="new-message-form" class="mb-6 bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg hidden">
        <div class="px-4 py-5 sm:p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Send a New Message</h2>
            
            <form action="messages.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="send_message">
                
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="recipient_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Recipient</label>
                        <select id="recipient_id" name="recipient_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select recipient</option>
                            <?php foreach ($recipients as $recipient): ?>
                                <option value="<?= $recipient['userID'] ?>">
                                    <?= htmlspecialchars($recipient['fullName']) ?> (<?= $recipient['role'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject</label>
                        <input type="text" id="subject" name="subject" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                    </div>
                    
                    <div>
                        <label for="message_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message</label>
                        <textarea id="message_text" name="message_text" rows="4" required class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" id="cancel-message" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                            Send Message
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Messages List -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <?php if (empty($messages)): ?>
                <div class="text-center py-10">
                    <i data-lucide="mail" class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600"></i>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No messages</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        You don't have any messages yet.
                    </p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">From/To</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Subject</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($messages as $message): ?>
                                <?php 
                                $isSender = $message['sender_id'] == $_SESSION['user_id'];
                                $isUnread = !$message['is_read'] && !$isSender;
                                ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 <?= $isUnread ? 'bg-blue-50 dark:bg-blue-900/10' : '' ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        <?php if ($isSender): ?>
                                            To: <?= htmlspecialchars($message['recipient_name']) ?> (<?= $message['recipient_role'] ?>)
                                        <?php else: ?>
                                            From: <?= htmlspecialchars($message['sender_name']) ?> (<?= $message['sender_role'] ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <a href="view_message.php?id=<?= $message['message_id'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            <?= htmlspecialchars($message['subject']) ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <?= date('M j, Y g:i a', strtotime($message['created_at'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($isUnread): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                Unread
                                            </span>
                                        <?php elseif (!$isSender): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                Read
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                Sent
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        <a href="view_message.php?id=<?= $message['message_id'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            View
                                        </a>
                                        <?php if ($isUnread): ?>
                                            | <a href="messages.php?mark_read=<?= $message['message_id'] ?>" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                Mark as Read
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // New message form toggle
    const newMessageBtn = document.getElementById('new-message-btn');
    const newMessageForm = document.getElementById('new-message-form');
    const cancelMessage = document.getElementById('cancel-message');
    
    newMessageBtn.addEventListener('click', function() {
        newMessageForm.classList.toggle('hidden');
    });
    
    cancelMessage.addEventListener('click', function() {
        newMessageForm.classList.add('hidden');
    });
    
    // If there's a form error, show the form again
    <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'send_message'): ?>
    newMessageForm.classList.remove('hidden');
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?> 