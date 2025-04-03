<?php
require 'config.php';
requireLogin();

// Initialize variables
$message = null;
$replies = [];
$error = null;
$success = null;

// Get message ID
$message_id = isset($_GET['id']) ? filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) : 0;

if (!$message_id) {
    header("Location: messages.php?error=Invalid message ID");
    exit();
}

// Handle sending a reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_reply') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        // Validate input
        $reply_text = trim($_POST['reply_text']);
        if (empty($reply_text)) {
            throw new Exception("Please enter a reply message.");
        }
        
        // Insert the reply
        $stmt = $pdo->prepare("INSERT INTO message_replies (message_id, sender_id, reply_text) VALUES (?, ?, ?)");
        $stmt->execute([$message_id, $_SESSION['user_id'], $reply_text]);
        
        // Get the original message details to notify the other party
        $stmt = $pdo->prepare("SELECT sender_id, recipient_id, subject FROM messages WHERE message_id = ?");
        $stmt->execute([$message_id]);
        $original = $stmt->fetch();
        
        if ($original) {
            // Determine who to notify (the person who didn't send this reply)
            $notify_id = ($_SESSION['user_id'] == $original['sender_id']) ? $original['recipient_id'] : $original['sender_id'];
            
            // Get sender name
            $stmt = $pdo->prepare("SELECT fullName FROM users WHERE userID = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $sender = $stmt->fetch();
            
            // Create notification
            createNotification(
                $notify_id, 
                "New Reply to Message", 
                $sender['fullName'] . " replied to: " . $original['subject'], 
                "message_reply", 
                $message_id, 
                "message"
            );
            
            // Mark the message as unread for the recipient
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 0 WHERE message_id = ? AND recipient_id = ?");
            $stmt->execute([$message_id, $notify_id]);
        }
        
        $success = "Your reply has been sent.";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch message
try {
    // Get message details
    $stmt = $pdo->prepare("
        SELECT m.*, 
               s.fullName as sender_name, s.role as sender_role, 
               r.fullName as recipient_name, r.role as recipient_role
        FROM messages m
        JOIN users s ON m.sender_id = s.userID
        JOIN users r ON m.recipient_id = r.userID
        WHERE m.message_id = ? AND (m.sender_id = ? OR m.recipient_id = ?)
        LIMIT 1
    ");
    $stmt->execute([$message_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $message = $stmt->fetch();
    
    if (!$message) {
        header("Location: messages.php?error=Message not found or you don't have permission to view it");
        exit();
    }
    
    // Mark message as read if recipient is viewing
    if ($message['recipient_id'] == $_SESSION['user_id'] && !$message['is_read']) {
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE message_id = ?");
        $stmt->execute([$message_id]);
        $message['is_read'] = 1;
    }
    
    // Get replies
    $stmt = $pdo->prepare("
        SELECT r.*, u.fullName, u.role
        FROM message_replies r
        JOIN users u ON r.sender_id = u.userID
        WHERE r.message_id = ?
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$message_id]);
    $replies = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error fetching message: " . $e->getMessage());
    $error = "Could not retrieve message. Please try again later.";
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex items-center">
        <a href="messages.php" class="inline-flex items-center mr-4 text-blue-600 dark:text-blue-400 hover:underline">
            <i data-lucide="arrow-left" class="h-4 w-4 mr-1"></i>
            Back to Messages
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($message['subject']) ?></h1>
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
    
    <!-- Message Details -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg mb-6">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">From:</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= htmlspecialchars($message['sender_name']) ?> (<?= $message['sender_role'] ?>)
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">To:</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= htmlspecialchars($message['recipient_name']) ?> (<?= $message['recipient_role'] ?>)
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Date:</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?= date('F j, Y g:i a', strtotime($message['created_at'])) ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Status:</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        <?php if ($message['is_read']): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Read
                            </span>
                        <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                Unread
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="px-6 py-5">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Message:</h3>
            <div class="prose dark:prose-invert max-w-none">
                <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap"><?= htmlspecialchars($message['message_text']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- Replies -->
    <?php if (!empty($replies)): ?>
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Replies (<?= count($replies) ?>)</h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($replies as $reply): ?>
            <div class="px-6 py-5">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="bg-blue-100 dark:bg-blue-900 rounded-full p-2">
                            <i data-lucide="user" class="h-5 w-5 text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="font-medium text-gray-900 dark:text-white">
                                <?= htmlspecialchars($reply['fullName']) ?> (<?= $reply['role'] ?>)
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= date('M j, Y g:i a', strtotime($reply['created_at'])) ?>
                            </p>
                        </div>
                        <div class="mt-2 text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                            <?= htmlspecialchars($reply['reply_text']) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Reply Form -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Reply</h3>
        </div>
        <div class="px-6 py-5">
            <form action="view_message.php?id=<?= $message_id ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="send_reply">
                
                <div class="mb-4">
                    <label for="reply_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your message</label>
                    <textarea id="reply_text" name="reply_text" rows="4" required class="block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i data-lucide="send" class="h-4 w-4 mr-2"></i>
                        Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 