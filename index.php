<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set webhook URL (Render.com will provide this)
// Format: https://your-app-name.onrender.com
$WEBHOOK_URL = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . '/index.php';

// Bot configuration
define('API_TOKEN', '8576540254:AAFPwxiXPk8HrARoXMCIQv-r-SunAj96JJU');
define('ADMIN_ID', 8205463301);
define('SUPPORT_USERNAME', '@Abutalha2025');

// File paths
define('USERS_FILE', __DIR__ . '/users.json');
define('ERROR_LOG', __DIR__ . '/error.log');
define('FILES_DIR', __DIR__ . '/received_files');

// Create files directory if not exists
if (!file_exists(FILES_DIR)) {
    mkdir(FILES_DIR, 0777, true);
}

// Initialize users.json if not exists
if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode([]));
    chmod(USERS_FILE, 0666);
}

// Handle incoming updates
$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update) {
    // If no update, show setup instructions
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo "Telegram Bot is running!<br>";
        echo "Webhook URL: " . $WEBHOOK_URL . "<br>";
        echo "To set webhook, visit:<br>";
        echo "https://api.telegram.org/bot" . API_TOKEN . "/setwebhook?url=" . urlencode($WEBHOOK_URL);
        exit;
    }
    exit;
}

// Log the update
file_put_contents(ERROR_LOG, date('Y-m-d H:i:s') . " - Update received: " . json_encode($update) . PHP_EOL, FILE_APPEND);

// Process the update
processUpdate($update);

function processUpdate($update) {
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $user_data = getUserData($chat_id);
        
        // Handle commands
        if (isset($message['entities']) && $message['entities'][0]['type'] === 'bot_command') {
            handleCommand($chat_id, $text, $user_data);
        } else {
            handleMessage($chat_id, $text, $message, $user_data);
        }
    }
}

function handleCommand($chat_id, $text, $user_data) {
    switch ($text) {
        case '/start':
        case '/menu':
            $user_data = [];
            saveUserData($chat_id, $user_data);
            sendMessage($chat_id, "👋 <b>মেইন মেনু</b>\nপেশাদার আইডি সাবমিট সিস্টেমে স্বাগতম।", [
                'keyboard' => [[['text' => '🚀 Start Submit'], ['text' => '📞 Support']]],
                'resize_keyboard' => true
            ]);
            break;
    }
}

function handleMessage($chat_id, $text, $message, $user_data) {
    $step = $user_data['step'] ?? 'main';
    
    // Handle cancel
    if ($text === '❌ Cancel') {
        $user_data = [];
        saveUserData($chat_id, $user_data);
        sendMessage($chat_id, "👋 <b>মেইন মেনু</b>\nপেশাদার আইডি সাবমিট সিস্টেমে স্বাগতম।", [
            'keyboard' => [[['text' => '🚀 Start Submit'], ['text' => '📞 Support']]],
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
                    [['text' => '📲 1000XXX Pc Clone Id Sell']],
                    [['text' => '📲 6155/6/7XXX Pc Clone Id Sell']],
                    [['text' => '❌ Cancel']]
                ],
                'resize_keyboard' => true
            ]);
        } elseif ($text === '📞 Support') {
            sendMessage($chat_id, "📞 <b>সাপোর্ট প্রয়োজন?</b>\nযোগাযোগ করুন: " . SUPPORT_USERNAME, [
                'keyboard' => [[['text' => '🚀 Start Submit'], ['text' => '📞 Support']]],
                'resize_keyboard' => true
            ]);
        }
    }
    
    // Handle work selection
    elseif ($step === 'work') {
        if ($text === '📲 1000XXX Pc Clone Id Sell' || $text === '📲 6155/6/7XXX Pc Clone Id Sell') {
            $user_data['work'] = $text;
            $user_data['step'] = 'username';
            saveUserData($chat_id, $user_data);
            sendMessage($chat_id, "🔗 <b>আপনার টেলিগ্রাম ইউজার নেম দিন:</b>", [
                'keyboard' => [[['text' => '❌ Cancel']]],
                'resize_keyboard' => true
            ]);
        } else {
            sendMessage($chat_id, "⚠️ দয়া করে বাটন থেকে বাছাই করুন:");
        }
    }
    
    // Handle username
    elseif ($step === 'username') {
        $user_data['username'] = $text;
        $user_data['step'] = 'count';
        saveUserData($chat_id, $user_data);
        sendMessage($chat_id, "🔢 <b>কত পিস আইডি দিয়েছেন তা লিখুন:</b>", [
            'keyboard' => [[['text' => '❌ Cancel']]],
            'resize_keyboard' => true
        ]);
    }
    
    // Handle count
    elseif ($step === 'count') {
        $user_data['count'] = $text;
        $user_data['step'] = 'payment';
        saveUserData($chat_id, $user_data);
        $example = "💰 <b>পেমেন্ট নম্বর এবং মাধ্যম লিখুন:</b>\n(বিকাশ / নগদ / রকেট)\n\nউদাহরণ: <code>016/7/8/9XXXXXXX bkash/Nagad/Rocket</code>";
        sendMessage($chat_id, $example, [
            'keyboard' => [[['text' => '❌ Cancel']]],
            'resize_keyboard' => true
        ]);
    }
    
    // Handle payment
    elseif ($step === 'payment') {
        $user_data['payment'] = $text;
        $user_data['step'] = 'file';
        saveUserData($chat_id, $user_data);
        sendMessage($chat_id, "📁 <b>এক্সেল (.xlsx) ফাইলটি আপলোড করুন:</b>", [
            'keyboard' => [[['text' => '❌ Cancel']]],
            'resize_keyboard' => true
        ]);
    }
    
    // Handle file upload
    elseif ($step === 'file' && isset($message['document'])) {
        $document = $message['document'];
        $file_id = $document['file_id'];
        $file_name = $document['file_name'];
        
        // Download file
        $file_path = getFile($file_id);
        if ($file_path) {
            $local_path = FILES_DIR . '/' . time() . '_' . $file_name;
            copy($file_path, $local_path);
            
            // Send to admin
            $caption = sprintf(
                "📩 <b>নতুন সাবমিশন!</b>\n━━━━━━━━━━━━━━━━━━━━\n👤 <b>ইউজার:</b> %s\n🛠 <b>কাজ:</b> %s\n🆔 <b>আইডি:</b> %s\n💰 <b>পেমেন্ট:</b> %s\n━━━━━━━━━━━━━━━━━━━━",
                $user_data['username'] ?? 'N/A',
                $user_data['work'] ?? 'N/A',
                $user_data['count'] ?? 'N/A',
                $user_data['payment'] ?? 'N/A'
            );
            
            sendDocument(ADMIN_ID, $file_id, $caption);
            
            // Clear user data
            $user_data = [];
            saveUserData($chat_id, $user_data);
            
            sendMessage($chat_id, "✅ <b>সাবমিশন সফল হয়েছে!</b>\nধন্যবাদ। আবার জমা দিতে চাইলে মেইন মেনুতে যান।", [
                'keyboard' => [[['text' => '🚀 Start Submit'], ['text' => '📞 Support']]],
                'resize_keyboard' => true
            ]);
        }
    } elseif ($step === 'file') {
        sendMessage($chat_id, "❌ ফাইল আপলোড করুন (Excel/Document):");
    }
}

function getUserData($chat_id) {
    $users = json_decode(file_get_contents(USERS_FILE), true) ?: [];
    return $users[$chat_id] ?? ['step' => 'main'];
}

function saveUserData($chat_id, $data) {
    $users = json_decode(file_get_contents(USERS_FILE), true) ?: [];
    $users[$chat_id] = $data;
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function sendMessage($chat_id, $text, $reply_markup = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
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

function getFile($file_id) {
    $response = apiRequest('getFile', ['file_id' => $file_id]);
    if ($response && isset($response['result']['file_path'])) {
        return 'https://api.telegram.org/file/bot' . API_TOKEN . '/' . $response['result']['file_path'];
    }
    return null;
}

function apiRequest($method, $data = []) {
    $url = 'https://api.telegram.org/bot' . API_TOKEN . '/' . $method;
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        file_put_contents(ERROR_LOG, date('Y-m-d H:i:s') . " - API Request failed: $method" . PHP_EOL, FILE_APPEND);
        return null;
    }
    
    return json_decode($result, true);
}

// Return 200 OK for Telegram
http_response_code(200);
?>