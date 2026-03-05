<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Bot configuration
define('API_TOKEN', '8576540254:AAFPwxiXPk8HrARoXMCIQv-r-SunAj96JJU');
define('ADMIN_ID', 8205463301);
define('SUPPORT_USERNAME', '@Abutalha2025');

// File paths
define('USERS_FILE', __DIR__ . '/users.json');
define('ERROR_LOG', __DIR__ . '/error.log');
define('FILES_DIR', __DIR__ . '/received_files');

// Initialize bot environment
function initializeBot() {
    // Create files directory
    if (!file_exists(FILES_DIR)) {
        mkdir(FILES_DIR, 0777, true);
    }
    
    // Create users.json if not exists
    if (!file_exists(USERS_FILE)) {
        file_put_contents(USERS_FILE, json_encode([]));
        chmod(USERS_FILE, 0666);
    }
    
    // Create error.log if not exists
    if (!file_exists(ERROR_LOG)) {
        file_put_contents(ERROR_LOG, '');
        chmod(ERROR_LOG, 0666);
    }
}

// Initialize on each request
initializeBot();

// Log function
function logError($message) {
    file_put_contents(ERROR_LOG, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

// Get webhook URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$WEBHOOK_URL = $protocol . $host . '/index.php';

// Handle GET request - Show setup info
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Telegram Bot Status</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
            .success { color: green; font-weight: bold; }
            .info { background: #f0f0f0; padding: 15px; border-radius: 5px; }
            code { background: #eee; padding: 2px 5px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <h1>🤖 Telegram Bot Status</h1>
        <p class='success'>✅ Bot is running successfully!</p>
        
        <div class='info'>
            <h3>📌 Setup Instructions:</h3>
            <p><strong>1. Set Webhook:</strong></p>
            <code>https://api.telegram.org/bot" . API_TOKEN . "/setwebhook?url=" . urlencode($WEBHOOK_URL) . "</code>
            
            <p><strong>2. Check Webhook Status:</strong></p>
            <code>https://api.telegram.org/bot" . API_TOKEN . "/getWebhookInfo</code>
            
            <p><strong>3. Your Bot URL:</strong> " . htmlspecialchars($WEBHOOK_URL) . "</p>
        </div>";
    
    // Check current webhook status
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.telegram.org/bot' . API_TOKEN . '/getWebhookInfo');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $webhookInfo = json_decode($response, true);
        echo "<h3>Current Webhook Status:</h3>";
        echo "<pre>" . htmlspecialchars(print_r($webhookInfo, true)) . "</pre>";
    } else {
        echo "<p style='color:red'>❌ Could not fetch webhook status. HTTP Code: $httpCode</p>";
    }
    
    echo "</body></html>";
    exit;
}

// Handle POST request - Telegram updates
$content = file_get_contents('php://input');
logError('Raw input: ' . $content);

if (empty($content)) {
    logError('Empty request received');
    http_response_code(400);
    exit;
}

$update = json_decode($content, true);

if (!$update) {
    logError('Invalid JSON: ' . $content);
    http_response_code(400);
    exit;
}

logError('Processing update for chat: ' . ($update['message']['chat']['id'] ?? 'unknown'));

// Process the update
try {
    processUpdate($update);
} catch (Exception $e) {
    logError('Error: ' . $e->getMessage());
}

// Return 200 OK
http_response_code(200);
echo 'OK';

function processUpdate($update) {
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user_data = getUserData($chat_id);
        
        // Log received message
        logError("Chat $chat_id: Received message: " . substr($text, 0, 50));
        
        // Handle commands
        if (isset($message['entities']) && !empty($message['entities']) && $message['entities'][0]['type'] === 'bot_command') {
            handleCommand($chat_id, $text, $user_data);
        } else {
            handleMessage($chat_id, $text, $message, $user_data);
        }
    }
}

function handleCommand($chat_id, $text, $user_data) {
    $command = strtok($text, ' ');
    logError("Chat $chat_id: Handling command: $command");
    
    switch ($command) {
        case '/start':
        case '/menu':
            $user_data = ['step' => 'main'];
            saveUserData($chat_id, $user_data);
            sendMessage($chat_id, "👋 <b>মেইন মেনু</b>\nপেশাদার আইডি সাবমিট সিস্টেমে স্বাগতম।", [
                'keyboard' => [['🚀 Start Submit', '📞 Support']],
                'resize_keyboard' => true
            ]);
            break;
            
        default:
            sendMessage($chat_id, "Unknown command. Use /start");
    }
}

function handleMessage($chat_id, $text, $message, $user_data) {
    $step = $user_data['step'] ?? 'main';
    logError("Chat $chat_id: Step: $step, Text: $text");
    
    // Handle cancel
    if ($text === '❌ Cancel') {
        $user_data = ['step' => 'main'];
        saveUserData($chat_id, $user_data);
        sendMessage($chat_id, "👋 <b>মেইন মেনু</b>\nপেশাদার আইডি সাবমিট সিস্টেমে স্বাগতম।", [
            'keyboard' => [['🚀 Start Submit', '📞 Support']],
            'resize_keyboard' => true
        ]);
        return;
    }
    
    // Handle main menu
    if ($step === 'main') {
        if ($text === '🚀 Start Submit') {
            $user_data['step'] = 'work';
            saveUserData($chat_id, $user_data);
            sendMessage($chat_id, "📁 <b>কাজের ধরন বাছাই করুন:</b>", [
                'keyboard' => [
                    ['📲 1000XXX Pc Clone Id Sell'],
                    ['📲 6155/6/7XXX Pc Clone Id Sell'],
                    ['❌ Cancel']
                ],
                'resize_keyboard' => true
            ]);
        } elseif ($text === '📞 Support') {
            sendMessage($chat_id, "📞 <b>সাপোর্ট প্রয়োজন?</b>\nযোগাযোগ করুন: " . SUPPORT_USERNAME, [
                'keyboard' => [['🚀 Start Submit', '📞 Support']],
                'resize_keyboard' => true
            ]);
        }
        return;
    }
    
    // Handle work selection
    if ($step === 'work') {
        if ($text === '📲 1000XXX Pc Clone Id Sell' || $text === '📲 6155/6/7XXX Pc Clone Id Sell') {
            $user_data['work'] = $text;
            $user_data['step'] = 'username';
            saveUserData($chat_id, $user_data);
            sendMessage($chat_id, "🔗 <b>আপনার টেলিগ্রাম ইউজার নেম দিন:</b>", [
                'keyboard' => [['❌ Cancel']],
                'resize_keyboard' => true
            ]);
        } else {
            sendMessage($chat_id, "⚠️ দয়া করে বাটন থেকে বাছাই করুন:");
        }
        return;
    }
    
    // Handle username
    if ($step === 'username') {
        $user_data['username'] = $text;
        $user_data['step'] = 'count';
        saveUserData($chat_id, $user_data);
        sendMessage($chat_id, "🔢 <b>কত পিস আইডি দিয়েছেন তা লিখুন:</b>", [
            'keyboard' => [['❌ Cancel']],
            'resize_keyboard' => true
        ]);
        return;
    }
    
    // Handle count
    if ($step === 'count') {
        $user_data['count'] = $text;
        $user_data['step'] = 'payment';
        saveUserData($chat_id, $user_data);
        $example = "💰 <b>পেমেন্ট নম্বর এবং মাধ্যম লিখুন:</b>\n(বিকাশ / নগদ / রকেট)\n\nউদাহরণ: <code>016/7/8/9XXXXXXX bkash/Nagad/Rocket</code>";
        sendMessage($chat_id, $example, [
            'keyboard' => [['❌ Cancel']],
            'resize_keyboard' => true
        ]);
        return;
    }
    
    // Handle payment
    if ($step === 'payment') {
        $user_data['payment'] = $text;
        $user_data['step'] = 'file';
        saveUserData($chat_id, $user_data);
        sendMessage($chat_id, "📁 <b>এক্সেল (.xlsx) ফাইলটি আপলোড করুন:</b>", [
            'keyboard' => [['❌ Cancel']],
            'resize_keyboard' => true
        ]);
        return;
    }
    
    // Handle file
    if ($step === 'file') {
        if (isset($message['document'])) {
            $document = $message['document'];
            $file_id = $document['file_id'];
            $file_name = $document['file_name'];
            
            // Get file path
            $file_path = getFilePath($file_id);
            
            if ($file_path) {
                // Save locally
                $local_path = FILES_DIR . '/' . time() . '_' . $file_name;
                
                // Download file
                $file_url = 'https://api.telegram.org/file/bot' . API_TOKEN . '/' . $file_path;
                $file_content = file_get_contents($file_url);
                
                if ($file_content) {
                    file_put_contents($local_path, $file_content);
                    
                    // Prepare caption
                    $caption = "📩 <b>নতুন সাবমিশন!</b>\n" .
                               "━━━━━━━━━━━━━━━━━━━━\n" .
                               "👤 <b>ইউজার:</b> " . ($user_data['username'] ?? 'N/A') . "\n" .
                               "🛠 <b>কাজ:</b> " . ($user_data['work'] ?? 'N/A') . "\n" .
                               "🆔 <b>আইডি:</b> " . ($user_data['count'] ?? 'N/A') . "\n" .
                               "💰 <b>পেমেন্ট:</b> " . ($user_data['payment'] ?? 'N/A') . "\n" .
                               "━━━━━━━━━━━━━━━━━━━━";
                    
                    // Forward to admin
                    sendDocument(ADMIN_ID, $file_id, $caption);
                    
                    // Clear user data
                    $user_data = ['step' => 'main'];
                    saveUserData($chat_id, $user_data);
                    
                    sendMessage($chat_id, "✅ <b>সাবমিশন সফল হয়েছে!</b>\nধন্যবাদ। আবার জমা দিতে চাইলে মেইন মেনুতে যান।", [
                        'keyboard' => [['🚀 Start Submit', '📞 Support']],
                        'resize_keyboard' => true
                    ]);
                    
                    logError("Chat $chat_id: File processed successfully: $file_name");
                }
            }
        } else {
            sendMessage($chat_id, "❌ দয়া করে একটি ফাইল আপলোড করুন:");
        }
    }
}

function getUserData($chat_id) {
    $users = json_decode(file_get_contents(USERS_FILE), true);
    if (!$users) {
        $users = [];
    }
    return $users[$chat_id] ?? ['step' => 'main'];
}

function saveUserData($chat_id, $data) {
    $users = json_decode(file_get_contents(USERS_FILE), true);
    if (!$users) {
        $users = [];
    }
    $users[$chat_id] = $data;
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function sendMessage($chat_id, $text, $keyboard = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($keyboard) {
        $data['reply_markup'] = json_encode([
            'keyboard' => $keyboard['keyboard'],
            'resize_keyboard' => $keyboard['resize_keyboard'] ?? true,
            'one_time_keyboard' => $keyboard['one_time_keyboard'] ?? false
        ]);
    }
    
    apiRequest('sendMessage', $data);
}

function sendDocument($chat_id, $document, $caption = '') {
    $data = [
        'chat_id' => $chat_id,
        'document' => $document,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
    apiRequest('sendDocument', $data);
}

function getFilePath($file_id) {
    $response = apiRequest('getFile', ['file_id' => $file_id]);
    if ($response && isset($response['result']['file_path'])) {
        return $response['result']['file_path'];
    }
    return null;
}

function apiRequest($method, $data = []) {
    $url = 'https://api.telegram.org/bot' . API_TOKEN . '/' . $method;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_error($ch)) {
        logError('CURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        logError("API Request failed: $method - HTTP $httpCode - Response: $response");
        return null;
    }
    
    return json_decode($response, true);
}
?>
