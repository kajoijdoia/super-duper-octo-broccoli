<?php

define("BOT_TOKEN", "8516698534:AAGt5NHV3BJJhmEEBgTrMvOf7OnCHqByoII");
define("API_URL", "https://api.telegram.org/bot" . BOT_TOKEN . "/");
define("CARTOON_API_URL", "https://viscodev.x10.mx/3D_CARTOON/api.php");
$user_states = [];
$user_messages = [];

function sendRequest($method, $parameters) {
    $url = API_URL . $method;  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function sendMessage($chatId, $text, $replyMarkup = null) {
    $parameters = [
        "chat_id" => $chatId,
        "text" => $text,
        "parse_mode" => "HTML"
    ];
    
    if ($replyMarkup) {
        $parameters["reply_markup"] = json_encode($replyMarkup);
    }
    
    return sendRequest("sendMessage", $parameters);
}

function sendPhoto($chatId, $photo, $caption = "", $replyMarkup = null) {
    $parameters = [
        "chat_id" => $chatId,
        "photo" => $photo,
        "caption" => $caption,
        "parse_mode" => "HTML"
    ];
    
    if ($replyMarkup) {
        $parameters["reply_markup"] = json_encode($replyMarkup);
    }
    
    return sendRequest("sendPhoto", $parameters);
}

function sendMediaGroup($chatId, $media) {
    $parameters = [
        "chat_id" => $chatId,
        "media" => json_encode($media)
    ];
    
    return sendRequest("sendMediaGroup", $parameters);
}

function generateCartoonImages($prompt) {
    $data = json_encode(["prompt" => $prompt]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => CARTOON_API_URL,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Content-Length: " . strlen($data),
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            "Accept: application/json",
            "Accept-Language: en-US,en;q=0.9",
            "Connection: keep-alive"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_COOKIEFILE => '', 
        CURLOPT_COOKIEJAR => '', 
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    

    if (strpos($response, '<html') !== false && strpos($response, 'aes.js') !== false) {
        sleep(2);
        return generateCartoonImagesRetry($prompt);
    }
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }
    }
    
    return false;
}

function generateCartoonImagesRetry($prompt) {
    $data = json_encode(["prompt" => $prompt]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => CARTOON_API_URL . '?t=' . time(), 
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Content-Length: " . strlen($data),
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
            "Accept: application/json, text/javascript, */*; q=0.01",
            "Accept-Language: en-US,en;q=0.9",
            "X-Requested-With: XMLHttpRequest",
            "Connection: keep-alive",
            "Referer: " . CARTOON_API_URL
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }
    }
    
    return false;
}

function editMessageText($chatId, $messageId, $text, $replyMarkup = null) {
    $parameters = [
        "chat_id" => $chatId,
        "message_id" => $messageId,
        "text" => $text,
        "parse_mode" => "HTML"
    ];
    
    if ($replyMarkup) {
        $parameters["reply_markup"] = json_encode($replyMarkup);
    }
    
    return sendRequest("editMessageText", $parameters);
}

function storeUserMessage($userId, $messageId, $text = null) {
    global $user_messages;
    
    if (!isset($user_messages[$userId])) {
        $user_messages[$userId] = [];
    }
    
    if ($text) {
        $user_messages[$userId][$messageId] = [
            'text' => $text,
            'timestamp' => time()
        ];
    } else {
        $user_messages[$userId][$messageId] = [
            'timestamp' => time()
        ];
    }
    
    foreach ($user_messages[$userId] as $msgId => $data) {
        if (time() - $data['timestamp'] > 3600) {
            unset($user_messages[$userId][$msgId]);
        }
    }
}

function getMainKeyboard() {
    return [
        'inline_keyboard' => [
            [
                ['text' => '🎨 Create Image', 'callback_data' => 'create_image'],
                ['text' => '📖 Help', 'callback_data' => 'help']
            ],
            [
                ['text' => '📢 Our Channel', 'url' => 'https://t.me/ANUJ_BOTS'],
                ['text' => '🔄 Refresh', 'callback_data' => 'refresh']
            ]
        ]
    ];
}

function getLoadingKeyboard() {
    return [
        'inline_keyboard' => [
            [
                ['text' => '⏳ Processing...', 'callback_data' => 'loading']
            ]
        ]
    ];
}

$input = file_get_contents("php://input");
$update = json_decode($input, true);

if (isset($update["callback_query"])) {
    $callback = $update["callback_query"];
    $callbackData = $callback["data"];
    $callbackChatId = $callback["message"]["chat"]["id"];
    $callbackMessageId = $callback["message"]["message_id"];
    $callbackUserId = $callback["from"]["id"];
    
    if ($callbackData === 'create_image') {
        editMessageText($callbackChatId, $callbackMessageId, "🎨 <b>Enter the description of the image you want to create:</b>\n\nWrite a precise description of the cartoon character you want, and I will create 5 different designs for you.", getMainKeyboard());
    } 
    elseif ($callbackData === 'help') {
        
        $helpText .= "🎯 <b>Advanced Examples:</b>\n";
        $helpText .= "• <code>Knight with decorated golden armor, holding a glowing sword, determined look</code>\n";
        $helpText .= "• <code>Girl with elegant yellow hair wearing purple robe, holding golden shoes</code>\n";
        $helpText .= "• <code>Space Explorer child in white space suit, open helmet, curious look</code>";
        
        editMessageText($callbackChatId, $callbackMessageId, $helpText, getMainKeyboard());
    }
    elseif ($callbackData === 'refresh') {
        $welcomeText = "🎬 <b>Welcome to the Professional 3D Cartoon Bot!</b> 🌟\n\n";
        $welcomeText .= "I'm here to create <b>high-quality 3D cartoon characters</b> 🏆\n\n";
        $welcomeText .= "📝 <b>How to get the best results:</b>\n";
        $welcomeText .= "• Write a detailed description of the character\n";
        $welcomeText .= "• Add fine details\n";
        $welcomeText .= "• Describe emotions and expressions\n\n";
        $welcomeText .= "🎯 <b>Professional examples:</b>\n";
        $welcomeText .= "• <code>Girl with long blonde hair and blue eyes, smiling, wearing blue dress</code>\n";
        $welcomeText .= "• <code>Brown bear with cute eyes, wearing red jacket, holding honey</code>\n";
        $welcomeText .= "• <code>Silver space robot with glowing green eyes, in heroic pose</code>\n\n";

        
        editMessageText($callbackChatId, $callbackMessageId, $welcomeText, getMainKeyboard());
    }
    
    sendRequest("answerCallbackQuery", [
        "callback_query_id" => $callback["id"]
    ]);
    
    exit;
}

if (!isset($update["message"])) {
    exit;
}

$message = $update["message"];
$chatId = $message["chat"]["id"];
$text = isset($message["text"]) ? $message["text"] : "";
$userId = $message["from"]["id"];
$userName = isset($message["from"]["first_name"]) ? $message["from"]["first_name"] : "User";
$messageId = $message["message_id"];

if ($text === "/start") {
    $welcomeText = "🎬 <b>Welcome to the Professional 3D Cartoon Bot!</b> 🌟\n\n";
    $welcomeText .= "I'm here to create <b>high-quality 3D cartoon characters</b> 🏆\n\n";
    $welcomeText .= "📝 <b>How to get the best results:</b>\n";
    $welcomeText .= "• Write a detailed description of the character\n";
    $welcomeText .= "• Add fine details\n";
    $welcomeText .= "• Describe emotions and expressions\n\n";
    $welcomeText .= "🎯 <b>Professional examples:</b>\n";
    $welcomeText .= "• <code>Girl with long blonde hair and blue eyes, smiling, wearing blue dress</code>\n";
    $welcomeText .= "• <code>Brown bear with cute eyes, wearing red jacket, holding honey</code>\n";
    $welcomeText .= "• <code>Silver space robot with glowing green eyes, in heroic pose</code>\n\n";
    
    $response = sendMessage($chatId, $welcomeText, getMainKeyboard());
    if ($response && isset($response['result']['message_id'])) {
        storeUserMessage($userId, $response['result']['message_id'], $welcomeText);
    }
} elseif ($text === "/help") {
  
    $helpText .= "🎯 <b>Advanced Examples:</b>\n";
    $helpText .= "• <code>Knight with decorated golden armor, holding a glowing sword, determined look</code>\n";
    $helpText .= "• <code>Girl with elegant yellow hair wearing purple robe, holding golden shoes</code>\n";
    $helpText .= "• <code>Space Explorer child in white space suit, open helmet, curious look</code>";
    
    $response = sendMessage($chatId, $helpText, getMainKeyboard());
    if ($response && isset($response['result']['message_id'])) {
        storeUserMessage($userId, $response['result']['message_id'], $helpText);
    }
} elseif (!empty($text) && $text[0] !== "/") {

    $waitingMessage = sendMessage($chatId, "• • • Creating and generating 3D image", getLoadingKeyboard());
    
    if ($waitingMessage && isset($waitingMessage['result']['message_id'])) {
        storeUserMessage($userId, $waitingMessage['result']['message_id']);
    }

    $result = generateCartoonImages($text);
    
    if ($result && isset($result["success"]) && $result["success"]) {

        $images = [];
        if (isset($result["images_with_background"]) && is_array($result["images_with_background"])) {
            $images = $result["images_with_background"];
        } elseif (isset($result["images"]) && is_array($result["images"])) {
            $images = $result["images"];
        } elseif (isset($result["with_background"]) && is_array($result["with_background"])) {
            $images = $result["with_background"];
        }
        
        $count = count($images);
        
        if ($count > 0) {

            $media = [];
            foreach ($images as $index => $imageUrl) {
                $media[] = [
                    'type' => 'photo',
                    'media' => $imageUrl,
                    'caption' => ($index === 0) ? "✨ 3D characters with professional CARTOON 3D quality" : ""
                ];
            }
            
            $mediaResponse = sendMediaGroup($chatId, $media);
            
            $finalText = "🎉 <b>Generation completed successfully!</b>";
            
            if ($waitingMessage && isset($waitingMessage['result']['message_id'])) {
                editMessageText($chatId, $waitingMessage['result']['message_id'], $finalText, getMainKeyboard());
            } else {
                $finalMessage = sendMessage($chatId, $finalText, getMainKeyboard());
                if ($finalMessage && isset($finalMessage['result']['message_id'])) {
                    storeUserMessage($userId, $finalMessage['result']['message_id'], $finalText);
                }
            }
            
        } else {
         
            $errorText = "❌ <b>Sorry, no images were created</b>\n\n";
            $errorText .= "🔧 <b>Possible reasons:</b>\n";
            $errorText .= "• Description is unclear\n";
            $errorText .= "• Server overload\n";
            $errorText .= "• Need more details\n\n";
            $errorText .= "💡 <b>Solution:</b>\n";
            $errorText .= "• Try again later\n";
            $errorText .= "• Add more details\n";
            $errorText .= "• Use clearer examples";
            
            if ($waitingMessage && isset($waitingMessage['result']['message_id'])) {
                editMessageText($chatId, $waitingMessage['result']['message_id'], $errorText, getMainKeyboard());
            } else {
                $errorMessage = sendMessage($chatId, $errorText, getMainKeyboard());
                if ($errorMessage && isset($errorMessage['result']['message_id'])) {
                    storeUserMessage($userId, $errorMessage['result']['message_id'], $errorText);
                }
            }
        }
    } else {
        $errorText = "❌ <b>Sorry, a technical error occurred</b>\n\n";
        $errorText .= "🔧 <b>Possible reasons:</b>\n";
        $errorText .= "• Description is unclear\n";
        $errorText .= "• Server overload\n";
        $errorText .= "• Need more details\n\n";
        $errorText .= "💡 <b>Solution:</b>\n";
        $errorText .= "• Try again later\n";
        $errorText .= "• Add more details\n";
        $errorText .= "• Use clearer examples";
        
        if ($waitingMessage && isset($waitingMessage['result']['message_id'])) {
            editMessageText($chatId, $waitingMessage['result']['message_id'], $errorText, getMainKeyboard());
        } else {
            $errorMessage = sendMessage($chatId, $errorText, getMainKeyboard());
            if ($errorMessage && isset($errorMessage['result']['message_id'])) {
                storeUserMessage($userId, $errorMessage['result']['message_id'], $errorText);
            }
        }
    }
} else {
    $unknownText = "🤔 <b>I didn't understand the command</b>\n\n";
    $unknownText .= "🎯 Write a detailed description of the character, or use:\n";
    $unknownText .= "/start - to begin\n";
    $unknownText .= "/help - for professional guide";
    
    $response = sendMessage($chatId, $unknownText, getMainKeyboard());
    if ($response && isset($response['result']['message_id'])) {
        storeUserMessage($userId, $response['result']['message_id'], $unknownText);
    }
}

exit;
?>
