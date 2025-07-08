<?php
function isUserAdmin(){
    global $TELEGRAM_TOKEN, $chat_id, $user_id;

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/getChatMember?chat_id=$chat_id&user_id=$user_id";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if($data && $data['ok']){
        $status = $data['result']['status'];
        return in_array($status, ['creator', 'administrator']);
    }

    return false;
}

function formatMarkdown($text){
    $specialChars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];

    foreach($specialChars as $char){
        $text = str_replace($char, '\\'.$char, $text);
    }

    $patterns = [
        '/\\\\\*(.*?)\\\\\*/' => '*$1*',
        '/\\\\\_(.*?)\\\\\_/' => '_$1_',
        '/\\\\\`(.*?)\\\\\`/' => '`$1`',
        '/\\\\\`\\\\\`\\\\\`(.*?)\\\\\`\\\\\`\\\\\`/s' => '```$1```',
        '/\\\\\[(.*?)\\\\\]\\\\\((.*?)\\\\\)/' => '[$1]($2)'
    ];

    foreach($patterns as $pattern => $replacement){
        $text = preg_replace($pattern, $replacement, $text);
    }

    return $text;
}

function formatForTelegram($text){
    // Guardar los bloques de código
    $codeBlocks = [];
    $text = preg_replace_callback(
        '/```(.*?)```/s',
        function($matches) use (&$codeBlocks){
            $id = count($codeBlocks);
            $codeBlocks[$id] = $matches[0]; // Guardamos el bloque original
            return "::::CODEBLOCK::$id::::";
        },
        $text
    );

    // Procesar el markdown normal
    $text = formatMarkdown($text);

    // Restaurar los bloques de código
    foreach($codeBlocks as $id => $code){
        $text = str_replace("::::CODEBLOCK::$id::::", $code, $text);
    }

    return $text;
}

function performWebSearch($query){
    global $SHAPES_API_KEY, $chat_id, $user_id, $SHAPE_USERNAME, $is_private, $using_key_id;
    $tPr = $is_private ? $SHAPE_USERNAME."/" : 'tg/';
    $tK = empty($using_key_id) ? 'tu/' : base64_encode($SHAPES_API_KEY)."/";
    $url = 'https://api.shapes.inc/v1/web/search';
    if(empty($chat_id)) $chat_id = -1;
    $headers = [
        "Authorization: Bearer $SHAPES_API_KEY",
        'Content-Type: application/json',
        "X-User-Id: ".$tK.$user_id
    ];
    if(!$is_private) $headers[] = "X-Channel-Id: ".$tPr.$chat_id;

    $data = ['query' => $query, 'max_results' => 50];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if($error) return "Error en la búsqueda: $error";

    $results = json_decode($response, true);
    if (!$results || !isset($results['results'])) {
        return "No se encontraron resultados";
    }

    $formatted_results = "";
    foreach($results['results'] as $item){
        $title = formatForTelegram($item['title'] ?? 'Sin título');
        $snippet = formatForTelegram($item['snippet'] ?? 'Sin descripción');
        $formatted_results .= "• [$title]({$item['url']})\n{$snippet}\n\n";

        if(strlen($formatted_results) > 3000){
            $formatted_results .= "[...]";
            break;
        }
    }

    return trim($formatted_results) ?: "No se encontraron resultados relevantes";
}

function getTelegramFileUrl($file_id){
    global $TELEGRAM_TOKEN;
    $file_info_url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/getFile?file_id=$file_id";
    $file_info = json_decode(file_get_contents($file_info_url), true);

    if($file_info && $file_info['ok']){
        $file_path = $file_info['result']['file_path'];
        return "https://api.telegram.org/file/bot$TELEGRAM_TOKEN/$file_path";
    }
    return null;
}

function call_shapes_api_with_queue($text, $api_key, $shape, $image_url = null, $audio_url = null){
  global $MAX_ATTEMPTS;
    $attempt = 0;
    $result = "Eh? (Error desconocido)";

    while($attempt < $MAX_ATTEMPTS){
        $result = call_shapes_api($text, $api_key, $shape, $image_url, $audio_url);

        if(strpos($result, "Error 429") === false) break;

        $wait_time = pow(2, $attempt) * $REQUEST_DELAY;
        sleep($wait_time);
        $attempt++;
    }

    return $result;
}

function call_shapes_api($text, $api_key, $shape, $image_url = null, $audio_url = null){
    global $chat_id, $user_id, $SHAPE_USERNAME, $SHAPE_NAME, $SHAPES_API_KEY, $is_private, $user_name, $using_key_id;
    $tPr = $is_private ? $SHAPE_USERNAME."/" : 'tg/';
    $tK = empty($using_key_id) ? 'tu/' : base64_encode($SHAPES_API_KEY)."/";
    $url = 'https://api.shapes.inc/v1/chat/completions';
    if(empty($chat_id)) $chat_id = -1;

    $headers = [
        "Authorization: Bearer $SHAPES_API_KEY",
        'Content-Type: application/json',
        "X-User-Id: ".$tK.$user_id
    ];
    if(!$is_private) $headers[] = "X-Channel-Id: ".$tPr.$chat_id;

    sendChatAction();

    $content = [];

    $content[] = ['type' => 'text', 'text' => $text];

    if($audio_url){
        $content[] = ['type' => 'audio_url', 'audio_url' => ['url' => $audio_url]];
    }elseif($image_url){
        $content[] = ['type' => 'image_url', 'image_url' => ['url' => $image_url]];
    }

    $data = [
        'model' => "shapesinc/$shape",
        'messages' => [['role' => 'user', 'content' => $content]]
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $response = curl_exec($ch);

    sendChatAction();

    if(curl_errno($ch)){
        curl_close($ch);
        return "Eh? (Error de conexión)";
    }

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http_code == 429){
        return "Eh? (Error 429 - Demasiadas solicitudes, el máximo global es *20* por minuto\.)";
    }elseif($http_code != 200){
        return "Eh? (Error $http_code)";
    }

    $json_response = json_decode($response, true);
    if(!$json_response || !isset($json_response['choices'][0]['message']['content'])) return "Eh? (Respuesta inválida)";

    return $json_response['choices'][0]['message']['content'];
}

function extractWebsiteContent($url){
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ShapesBot/1.0)',
        CURLOPT_TIMEOUT => 10
    ]);

    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http_code != 200 || empty($html)) return false;

    // Extraer texto del HTML
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    // Eliminar elementos no deseados
    foreach($xpath->query('//script|//style|//iframe|//nav') as $node){
        $node->parentNode->removeChild($node);
    }

    return trim($dom->textContent);
}

function generate_image_with_shapes($prompt, $api_key, $shape){
  global $chat_id, $user_id, $SHAPES_API_KEY, $SHAPE_USERNAME, $is_private, $using_key_id;
    $tPr = $is_private ? $SHAPE_USERNAME."/" : 'tg/';
    $tK = empty($using_key_id) ? 'tu/' : base64_encode($SHAPES_API_KEY)."/";
    $url = 'https://api.shapes.inc/v1/images/generate';
    if(empty($chat_id)) $chat_id = -1;
    $headers = [
        "Authorization: Bearer $SHAPES_API_KEY",
        'Content-Type: application/json',
        "X-User-Id: ".$tK.$user_id
    ];
    if(!$is_private) $headers[] = "X-Channel-Id: ".$tPr.$chat_id;

    $img_res = ['512x512', '1024x1024', '768x1024', '576x1024', '1024x768', '1024x576'];

    $data = [
        'model' => "shapesinc/$shape",
        'prompt' => $prompt,
        'size' => $img_res[array_rand($img_res)],
        'quality' => 'standard',
        'response_format' => 'url'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http_code == 200){
        $json = json_decode($response, true);
        return $json['data'][0]['url'] ?? false;
    }

    return false;
}

function sendMessage($text){
    global $TELEGRAM_TOKEN, $chat_id;

    $messages = splitLongMessage($text);
    foreach($messages as $index => $message_part){
        $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendMessage?chat_id=$chat_id&text=".urlencode($message_part)."&parse_mode=MarkdownV2";
        file_get_contents($url);

        // Pequeña pausa entre mensajes (excepto el último)
        if($index < count($messages) - 1) usleep(500000); // 0.5s
    }
}

function sendReply($reply_to_message_id, $text){
    global $TELEGRAM_TOKEN, $chat_id;

    $messages = splitLongMessage($text);
    $first_part = true;

    foreach($messages as $message_part){
        if($first_part){
            // Solo el primer mensaje lleva reply
            $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendMessage?chat_id=$chat_id&reply_to_message_id=$reply_to_message_id&text=".urlencode($message_part)."&parse_mode=MarkdownV2";
            $first_part = false;
        }else{
            $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendMessage?chat_id=$chat_id&text=".urlencode($message_part)."&parse_mode=MarkdownV2";
        }

        file_get_contents($url);
        usleep(500000);
    }
}

function splitLongMessage($text, $max_length = 4096){
    $messages = [];
    $text = trim($text);

    // Si el texto ya cabe en un mensaje, retornarlo directamente
    if(strlen($text) <= $max_length) return [$text];

    // Patrón para detectar enlaces Markdown
    $link_pattern = '/\[([^\]]+)\]\(((?:https?|ftp):\/\/[^\)]+)\)/';

    while(strlen($text) > $max_length){
        $split_pos = $max_length;
        $chunk = substr($text, 0, $max_length);

        // Buscar enlaces en el chunk actual
        preg_match_all($link_pattern, $chunk, $matches, PREG_OFFSET_CAPTURE);

        $in_link = false;
        foreach($matches[0] as $match){
            $link_start = $match[1];
            $link_end = $link_start + strlen($match[0]);

            // Si el punto de división corta un enlace
            if($split_pos > $link_start && $split_pos < $link_end){
                $in_link = true;
                $split_pos = $link_end;
                break;
            }
        }

        // Si no estamos en un enlace, buscar el último espacio
        if(!$in_link){
            $space_pos = strrpos($chunk, ' ');
            if($space_pos !== false && $space_pos > $max_length * 0.8){
                $split_pos = $space_pos;
            }
        }

        // Asegurar que no dividimos códigos Markdown
        $markdown_chars = ['*', '_', '`', '~'];
        foreach($markdown_chars as $char){
            $count = substr_count($chunk, $char);
            if($count % 2 != 0){
                // Hay un carácter sin pareja, buscar la próxima ocurrencia
                $next_pos = strpos($text, $char, $split_pos);
                if($next_pos !== false){
                    $split_pos = $next_pos + 1;
                }
            }
        }

        $messages[] = substr($text, 0, $split_pos);
        $text = ltrim(substr($text, $split_pos));
    }

    if(!empty($text)) $messages[] = $text;

    return $messages;
}

function sendChatAction($action = 'typing') {
    global $TELEGRAM_TOKEN, $chat_id;

    $valid_actions = ['typing', 'upload_photo', 'record_video', 'upload_video', 'record_voice', 'upload_voice', 'upload_document', 'choose_sticker', 'find_location'];

    if(!in_array($action, $valid_actions)) $action = 'typing';

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendChatAction";
    $postData = http_build_query(['chat_id' => $chat_id, 'action' => $action]);

    // Método 1
    if(function_exists('curl_init')){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 1, // 1 segundo máximo
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);
        return true;
    }

    if(function_exists('fsockopen')){
        $parts = parse_url($url);
        $host = $parts['host'];
        $port = isset($parts['port']) ? $parts['port'] : ($parts['scheme'] === 'https' ? 443 : 80);
        $path = $parts['path'] ?? '/';

        $fp = @fsockopen(
            ($parts['scheme'] === 'https' ? 'ssl://' : '') . $host, 
            $port, 
            $errno, 
            $errstr, 
            1 // Timeout
        );

        if($fp){
            $out = "POST $path HTTP/1.1\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "Content-Length: ".strlen($postData)."\r\n";
            $out .= "Connection: Close\r\n\r\n";
            $out .= $postData;
            
            fwrite($fp, $out);
            fclose($fp);
        }
        return true;
    }
    // Método 3
    @file_get_contents($url.'?'.http_build_query($data));
    return true;
}

function editMessageText($chat_id, $message_id, $text){
    global $TELEGRAM_TOKEN;

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/editMessageText";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'MarkdownV2'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
}

function deleteMessage($chat_id, $message_id){
    global $TELEGRAM_TOKEN;

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/deleteMessage";
    $post_fields = [
        'chat_id' => $chat_id,
        'message_id' => $message_id
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

function makeShapesApiRequest($text, $headers){
    global $SHAPE_USERNAME;
    $url = 'https://api.shapes.inc/v1/chat/completions';
    $data = [
        'model' => "shapesinc/$SHAPE_USERNAME",
        'messages' => [['role' => 'user', 'content' => [['type' => 'text', 'text' => $text]]]]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25
    ]);

    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function sendReplyWithKeyboard($reply_to_message_id, $text, $keyboard){
    global $TELEGRAM_TOKEN, $chat_id;

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendMessage";

    $post_fields = [
        'chat_id' => $chat_id,
        'reply_to_message_id' => $reply_to_message_id,
        'text' => $text,
        'parse_mode' => 'MarkdownV2',
        'reply_markup' => json_encode($keyboard)
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: multipart/form-data"]
    ]);

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($http_code != 200){
        $post_fields['parse_mode'] = null;
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_RETURNTRANSFER => true
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

function answerCallbackQuery($callback_query_id, $text = ""){
    global $TELEGRAM_TOKEN;
    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/answerCallbackQuery";
    $data = [
        'callback_query_id' => $callback_query_id,
        'text' => $text
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

function strToNmb($texto){
    $numero = '0'; // Str
    for($i = 0; $i < strlen($texto); $i++){
        $codigo = ord($texto[$i]); // ASCII
        $numero = bcmul($numero, '256');
        $numero = bcadd($numero, (string)$codigo);
    }
    return $numero;
}

function setMessageReaction($reaction = null, $is_big = false){
    global $TELEGRAM_TOKEN, $chat_id, $message_id;

    $bot_permissions = getBotPermissions();
    if(!$bot_permissions || !$bot_permissions['can_send_messages']) return false;

    $valid_reactions = [];
    if($reaction !== null){
        $reaction_array = is_array($reaction) ? $reaction : [$reaction];
        foreach($reaction_array as $emoji){
            if(isValidEmoji($emoji)) $valid_reactions[] = $emoji;
        }
        if(empty($valid_reactions)) return false;
    }

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/setMessageReaction";
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
    ];

    if(!empty($valid_reactions)){
        $data['reaction'] = json_encode(array_map(function($emoji){
            return ['type' => 'emoji', 'emoji' => $emoji];
        }, $valid_reactions));
    }

    if($is_big) $data['is_big'] = $is_big;

    // Método 1
    if(function_exists('curl_init')){
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_NOSIGNAL => 1, // Importante para no bloquear
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        curl_exec($ch);
        curl_close($ch);
        return true;
    }

    // Método 2:
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data),
            'timeout' => 1
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    @file_get_contents($url, false, $context);

    return true;
}

function getBotPermissions(){
    global $TELEGRAM_TOKEN, $chat_id;

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/getChat?chat_id=$chat_id";
    $response = @file_get_contents($url);

    if(!$response){
        return false;
    }

    $data = json_decode($response, true);

    if(!$data || !$data['ok']){
        return false;
    }

    if(isset($data['result']['permissions'])){
        return $data['result']['permissions'];
    }

    return ['can_send_messages' => true, 'can_send_media_messages' => true];
}

function isValidEmoji($emoji){
    $emoji_pattern = '/^[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{FE0E}\x{FE0F}\x{1F1E6}-\x{1F1FF}]+$/u';

    return preg_match($emoji_pattern, $emoji);
}
?>