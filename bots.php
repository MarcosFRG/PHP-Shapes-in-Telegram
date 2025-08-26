<?php
require_once "../config.php";
require "../funcs.php";

ini_set('max_execution_time', 60);
set_time_limit(60);

global $MAX_RETRIES, $MAX_ATTEMPTS, $REQUEST_DELAY, $ERROR_MSG, $bot_action, $SHAPE_USERNAME, $SHAPES_API_KEY, $chat_id, $user_id, $using_key_id, $is_private, $keys_file, $user_name, $bot_mention;
$MAX_RETRIES = 3;
$MAX_ATTEMPTS = 10;
$REQUEST_DELAY = 2;
$ACTIVATION_FOLDER = 'Activated';
$FREEWILL_FOLDER = 'No_Free-will';

$bot_action = 0;

if(!isset($EXTRA_HELP)) $EXTRA_HELP = '';
$EXTRA_HELP .= ($SHAPE_COMMAND_DICE==true?"
/dice - Lanza un dado.":'').($SHAPE_COMMAND_8BALL==true?"
/8ball - ¡Recibe una respuesta mágica!":'');

if(empty($START_MSG)) $START_MSG=$DEFSTART_MSG;
if(empty($ERROR_MSG)) $ERROR_MSG=$DEFERROR_MSG;
if(empty($ACTIVATE_MSG)) $ACTIVATE_MSG=$DEFACTIVATE_MSG;
if(empty($DEACTIVATE_MSG)) $DEACTIVATE_MSG=$DEFDEACTIVATE_MSG;
if(strpos($ADMINSONLY_MSG, "\\") === false) $ADMINSONLY_MSG = formatForTelegram($ADMINSONLY_MSG);

if(!file_exists($ACTIVATION_FOLDER)) mkdir($ACTIVATION_FOLDER, 0777, true);
if(!file_exists($FREEWILL_FOLDER)) mkdir($FREEWILL_FOLDER, 0777, true);

$php_input = file_get_contents('php://input');
$update = json_decode($php_input, true);
$callback_query = $update['callback_query'] ?? null;
$message = $update['message'] ?? null;

if($callback_query){
    $callback_data = $callback_query['data'];
    $callback_message_id = $callback_query['message']['message_id'];
    $chat_id = $callback_query['message']['chat']['id'];
    $user_id = $callback_query['from']['id'];
    $is_private = ($callback_query['message']['chat']['type'] === 'private');

    $keys_file = "../users/$user_id/keys.json";
    $using_key_id = file_get_contents("../users/$user_id/$SHAPE_USERNAME.txt");
    $keys_data = [];
    if(file_exists($keys_file)){
        $keys_data = json_decode(file_get_contents($keys_file), true);
    }
    if(!empty($using_key_id)) $SHAPES_API_KEY = OpenSSL_Dec($keys_data[$using_key_id]['key']);

    answerCallbackQuery($callback_query['id']);

    switch($callback_data){
      case "fw_1":
        if(!$is_private && !isUserAdmin() && $FW_EVERYONE != true){
          sendMessage($ADMINSONLY_MSG);
          exit;
        }
        if(file_exists("$FREEWILL_FOLDER/$chat_id.txt")){
          unlink("$FREEWILL_FOLDER/$chat_id.txt");
        }
        editMessageText($chat_id, $callback_message_id, "¡Free\-will activado\!");
        break;
      case "fw_0":
        if(!$is_private && !isUserAdmin() && $FW_EVERYONE != true){
          sendMessage($ADMINSONLY_MSG);
          exit;
        }
        if(!file_exists("$FREEWILL_FOLDER/$chat_id.txt")){
          file_put_contents("$FREEWILL_FOLDER/$chat_id.txt","");
        }
        editMessageText($chat_id, $callback_message_id, "¡Free\-Will desactivado\!");
        break;
      case "reset":
        if(!$is_private && !isUserAdmin()){
          sendMessage($ADMINSONLY_MSG);
          exit;
        }
        editMessageText($chat_id, $callback_message_id, formatForTelegram(call_shapes_api_with_queue('!reset', $SHAPES_API_KEY, $SHAPE_USERNAME)));
        break;
      case "delete":
        if(!$is_private && !isUserAdmin()){
          sendMessage($ADMINSONLY_MSG);
          exit;
        }
        deleteMessage($chat_id, $callback_message_id);
    }
    exit;
}

$chat_id = $message['chat']['id'];
$message_id = $message['message_id'];
$chat_type = $message['chat']['type'];
$user_text = $message['text'] ?? $message['caption'] ?? '';
$is_private = ($chat_type === 'private');
$is_group = ($chat_type === 'group' || $chat_type === 'supergroup');
$group_file = "$ACTIVATION_FOLDER/$chat_id.txt";

$sticker = $message['sticker'];
if(!empty($sticker)) $user_text = '["sticker_name": "'.$sticker['set_name'].'", "sticker_emoji": "'.$sticker['emoji'].'"]';

$is_free = !file_exists("$FREEWILL_FOLDER/$chat_id.txt");

$user = $message['from'];
$user_name = (string)$user['first_name'] ?? 'Desconocido';
$user_id = (string)$user['id'];

$START_MSG = formatForTelegram(replaceVars($START_MSG));
$ERROR_MSG = formatForTelegram(replaceVars($ERROR_MSG));
$ACTIVATE_MSG = formatForTelegram(replaceVars($ACTIVATE_MSG));
$DEACTIVATE_MSG = formatForTelegram(replaceVars($DEACTIVATE_MSG));

$bot_mention = $is_group?"@$BOT_USERNAME":'';

if(trim($user_text) == "/start$bot_mention"){
  if($is_private){
    sendMessage($START_MSG);
  }else{
    setMessageReaction("👍");
  }
  exit;
}elseif(trim($user_text) == "/help$bot_mention"){
  $response = formatForTelegram(replaceVars("$HELP_CMD
$EXTRA_HELP"));
  $is_private?sendMessage($response):sendReply($message_id, $response);
  exit;
}

if(!file_exists("../users/$user_id"))mkdir("../users/$user_id", 0777, true);

$using_key_id = file_get_contents("../users/$user_id/$SHAPE_USERNAME.txt");
$keys_file = "../users/$user_id/keys.json";
$keys_data = [];
if(file_exists($keys_file)){
    $keys_data = json_decode(file_get_contents($keys_file), true);
}

if(!empty($using_key_id)) $SHAPES_API_KEY = OpenSSL_Dec($keys_data[$using_key_id]['key']);

$is_reply_to_bot = false;
$replying_to_user = "";
if(isset($message['reply_to_message'])){
    $reply_from = $message['reply_to_message']['from'];
    $is_reply_to_bot = (isset($reply_from['username']) && $reply_from['username'] === $BOT_USERNAME);

    if(!$is_reply_to_bot) $replying_to_user = $reply_from['first_name'] ?? 'Usuario';
}

if($is_group && strpos($user_text, "/") === 0 && (strpos($user_text, "@") < 2 || strpos($user_text, $BOT_USERNAME) === false)) exit;

if($is_group){
    if(strpos($user_text, "/activate@$BOT_USERNAME") === 0){
        if(isUserAdmin()){
            file_put_contents($group_file, 'active');
            sendReply($message_id, $ACTIVATE_MSG);
        }else{
            sendReply($message_id, "❌ Solo los administradores pueden activarme.");
        }
        exit;
    }elseif(strpos($user_text, "/deactivate@$BOT_USERNAME") === 0){
        if(isUserAdmin()){
            if(file_exists($group_file)){
                unlink($group_file);
            }
            sendReply($message_id, $DEACTIVATE_MSG);
        }else{
            sendReply($message_id, "❌ Solo los administradores pueden desactivarme.");
        }
        exit;
    }
}

if(($is_group || $is_private) && strpos($user_text, "/wack$bot_mention") === 0){
  if($is_group && !isUserAdmin()){
    sendReply($message_id, $ADMINSONLY_MSG);
    exit;
  }
  $reply = formatForTelegram(call_shapes_api_with_queue('!wack', $SHAPES_API_KEY, $SHAPE_USERNAME));
  $is_group?sendReply($message_id, $reply):sendMessage($reply);
  exit;
}
// /reset
elseif(($is_group || $is_private) && strpos($user_text, "/reset$bot_mention") === 0){
    if($is_group && !isUserAdmin()){
        sendReply($message_id, $ADMINSONLY_MSG);
        exit;
    }

    $keyboard = ['inline_keyboard' => [[['text' => '✅ Sí', 'callback_data' => 'reset'], ['text' => '❌ No',  'callback_data' => 'delete']]]];

    $confirm_message = "¿Estás segur@ de que quieres *borrar la memoria de ". formatForTelegram($SHAPE_NAME)."* en este ".($is_private?'MD':'chat')." *para siempre*?";

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendMessage";
    $data = ['chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => $confirm_message, 'parse_mode' => 'MarkdownV2', 'reply_markup' => json_encode($keyboard)];
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    exit;
}
// /freewill
elseif(($is_group || $is_private) && strpos($user_text, "/freewill$bot_mention") === 0){
    if($is_group && !isUserAdmin() && $FW_EVERYONE != true){
        sendReply($message_id, $ADMINSONLY_MSG);
        exit;
    }

    $keyboard = ['inline_keyboard' => [[['text' => '✅ Sí', 'callback_data' => 'fw_1'], ['text' => '❌ No',  'callback_data' => 'fw_0']]]];

    $confirm_message = "¿Quieres activar el Free\-will de *".formatForTelegram($SHAPE_NAME)."* en este ".($is_private?'MD':'chat')."?";

    $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendMessage";
    $data = ['chat_id' => $chat_id, 'reply_to_message_id' => $message_id, 'text' => $confirm_message, 'parse_mode' => 'MarkdownV2', 'reply_markup' => json_encode($keyboard)];
  
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    exit;
}
// /imagine
elseif(strpos($user_text, "/imagine$bot_mention") === 0){
    $prompt = trim(str_replace("/imagine$bot_mention", "", $user_text));

    if(!empty($prompt)){
        sendChatAction('upload_photo');

        $response = call_shapes_api_with_queue("!imagine $prompt", $SHAPES_API_KEY, $SHAPE_USERNAME);

        preg_match('/https?:\/\/[^\s]+/', $response, $matches);
        $image_url = $matches[0] ?? null;
        $caption = str_replace($image_url, '', $response);
        $caption = trim(str_replace($SHAPE_NAME.":", "", $caption));
        $caption = formatForTelegram($caption);

        if($image_url){
            $url = "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendPhoto";
            $post_fields = ['chat_id' => $chat_id, 'photo' => $image_url, 'caption' => $caption, 'parse_mode' => 'MarkdownV2', 'reply_to_message_id' => $is_group?$message_id:null];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }else{
            // Si no hay imagen, solo enviar el txt
            $is_group?sendReply($message_id, $caption):sendMessage($caption);
        }
    }else{
        $reply = "Uso: /imagine".str_replace("_", "\_", $bot_mention)." \[prompt\]";
        $is_group?sendReply($message_id, $reply):sendMessage($reply);
    }
    exit;
}
// /web
elseif(strpos($user_text, "/web$bot_mention") === 0){
    $search_query = trim(str_replace("/web$bot_mention", "!web", $user_text));

    if(!empty($search_query)){
        sendChatAction('find_location');

        $response = call_shapes_api_with_queue($search_query, $SHAPES_API_KEY, $SHAPE_USERNAME);

        $clean_response = str_replace(trim($SHAPE_NAME).":", "", trim($response));
        $formatted_response = formatForTelegram($clean_response);

        if(!empty($formatted_response)){
            $is_group?sendReply($message_id, $formatted_response):sendMessage($formatted_response);
        }
    }else{
        sendReply($message_id, "Uso: /web".str_replace(["_", "-"], ["\_", "\-"], $bot_mention)." \[mensaje\]");
    }
    exit;
}
// /http
elseif(strpos($user_text, "/http$bot_mention") === 0){
    $url = trim(str_replace("/http$bot_mention", "", $user_text));
    if(!empty($url)){
        sendChatAction('find_location');
        // Validar y limpiar la URL
        if(!preg_match('/^http?:\/\//i', $url)){
            $url = 'https://'.$url; // Añadir protocolo si falta
        }
        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Extraer contenido de la URL
        $website_content = extractWebsiteContent($url);

        if($website_content === false){
            $response = call_shapes_api_with_queue(
                "```HTTP
No se puede acceder al contenido de la URL: $url.
```",
                $SHAPES_API_KEY,
                $SHAPE_USERNAME
            );
        }else{
            // Enviar contenido a la shape
            $truncated_content = substr($website_content, 0, 15000);
            $response = call_shapes_api_with_queue(
                "```HTTP
Contenido de '$url'.:\n\n$truncated_content
```",
                $SHAPES_API_KEY,
                $SHAPE_USERNAME
            );
        }

        // Procesar y enviar la respuesta
        $clean_response = trim(str_replace($SHAPE_NAME.":", "", $response));
        $formatted_response = formatForTelegram($clean_response);

        $is_group?sendReply($message_id, $formatted_response):sendMessage($formatted_response);
    }else{
        sendReply($message_id, "🌐 Uso: /http".str_replace("_", "\_", $bot_mention)." \[url\]\nEj: /http".str_replace("_", "\_", $bot_mention)." https://ejemplo.com");
    }
    exit;
}
// /dashboard
elseif(strpos($user_text, "/dashboard$bot_mention") === 0){
  $response = formatForTelegram(call_shapes_api_with_queue("!dashboard", $SHAPES_API_KEY, $SHAPE_USERNAME));
  $is_group?sendReply($message_id, $response):sendMessage($response);
  exit;
}
// /info
elseif(strpos($user_text, "/info$bot_mention") === 0){
  $response = formatForTelegram(call_shapes_api_with_queue("!info", $SHAPES_API_KEY, $SHAPE_USERNAME));
  $is_group?sendReply($message_id, $response):sendMessage($response);
  exit;
}
// /sleep
elseif(strpos($user_text, "/sleep$bot_mention") === 0){
  $response = formatForTelegram(call_shapes_api_with_queue("!sleep", $SHAPES_API_KEY, $SHAPE_USERNAME));
  $is_group?sendReply($message_id, $response):sendMessage($response);
  exit;
}
// /ask
elseif($is_group && strpos($user_text, "/ask$bot_mention ") === 0){
  $response = formatForTelegram(call_shapes_api_with_queue(str_replace("/ask$bot_mention ", "", $user_text), $SHAPES_API_KEY, $SHAPE_USERNAME));
  sendReply($message_id, $response);
  exit;
}
// /register
elseif(strpos($user_text, "/register$bot_mention") === 0){
  if($is_group){
    sendReply($message_id, $MDONLY_MSG);
  exit;
  }
  $lines = explode("
", trim($user_text));
if(count($lines) == 3){
    $custom_name = trim($lines[1]);
    $api_key = trim($lines[2]);

    $new_id = strval(count($keys_data)+ 1);

    // Guardar la nueva key
    $keys_data[$new_id] = ["name" => $custom_name, "key" => OpenSSL_Enc($api_key)];

    // Escribir en el archivo
    file_put_contents($keys_file, json_encode($keys_data, JSON_PRETTY_PRINT));

    sendMessage("✅ API Key registrada con ID: $new_id, para usarla utiliza el comando '/setkey $new_id'");
}else{
    sendMessage("❌ Formato inválido\. Usa:
/register
Nombre
API\_Key");
}
exit;
}
// /mykeys
elseif(strpos($user_text, "/mykeys$bot_mention") === 0){
  if($is_group){
    sendReply($message_id, $MDONLY_MSG);
    exit;
  }
  if(file_exists($keys_file)){
    $keys_count = count($keys_data);
    $keys_str = "
";
    foreach($keys_data as $key_id => $key_info){
      $keys_str .= "

    $key_id\. `".$key_info["name"]."`  →  `".OpenSSL_Dec($key_info["key"])."`";
    }
    $using_key = $keys_data[file_get_contents("../users/$user_id/$SHAPE_USERNAME.txt")]["name"];
    $using_key = empty($using_key)?"T":"Actualmente estoy usando `$using_key`, y t";
    sendMessage($keys_count>0?($using_key."ienes *$keys_count* API Keys registradas: $keys_str"):"❌ No tienes keys registradas\. Usa /register primero\.");
  }else{
    sendMessage("No tienes API Keys guardadas\.");
  }
  exit;
}
// /setkey
elseif(strpos($user_text, "/setkey") === 0){
    $parts = explode(" ", $user_text);
    if(count($parts) >= 2){
        $key_id = trim($parts[1]);

      if($key_id <= 0){
        unlink("../users/$user_id/$SHAPE_USERNAME.txt");
        sendMessage("✅ Usando API Key por defecto\.");
        exit;
      }

        if(file_exists($keys_file)){
            if(isset($keys_data[$key_id])){
                file_put_contents("../users/$user_id/$SHAPE_USERNAME.txt", $key_id);
                sendMessage("✅ API Key activada: `".$keys_data[$key_id]['name']."`");
            }else{
                sendMessage("❌ No existe una key con ID *$key_id*.");
            }
        }else{
            sendMessage("❌ No tienes keys registradas\. Usa /register primero\.");
        }
    }else{
        sendMessage("❌ Uso: /setkey \[ID\]");
    }
    exit;
}
// /deletekey
elseif(strpos($user_text, "/deletekey$bot_mention") === 0){
    $parts = explode(" ", $user_text);
    if(count($parts) >= 2){
        $key_id = trim($parts[1]);

        // Verificar si existe la key
        if(isset($keys_data[$key_id])){
            $key_name = $keys_data[$key_id]['name'];
            $is_active = file_exists("../users/$user_id/$SHAPE_USERNAME.txt") && 
                         file_get_contents("../users/$user_id/$SHAPE_USERNAME.txt") == $key_id;

            // Eliminar la key
            unset($keys_data[$key_id]);

            // Reordenar las keys
            $new_keys_data = [];
            $new_index = 1;
            foreach($keys_data as $old_id => $key_info){
                $new_keys_data[$new_index] = $key_info;
                $new_index++;
            }

            file_put_contents($keys_file, json_encode($new_keys_data, JSON_PRETTY_PRINT));
            $keys_data = $new_keys_data;

            // Actualizar la key activa si es necesario
            $active_key_file = "../users/$user_id/$SHAPE_USERNAME.txt";
            if($is_active){
                unlink($active_key_file);
                $response = "🗑️ Key eliminada: *$key_name*\n⚠️ Era tu key activa, ahora usarás la key por defecto\.";
            }else{
                // Si hay una key activa, actualizar su ID si era mayor que la eliminada
                if(file_exists($active_key_file)){
                    $current_active_id = file_get_contents($active_key_file);
                    if($current_active_id > $key_id){
                        $new_active_id = $current_active_id - 1;
                        file_put_contents($active_key_file, $new_active_id);
                        $active_key_name = $keys_data[$new_active_id]['name'];
                        $response = "🗑️ Key eliminada: *$key_name*\nℹ️ Tu key activa se actualizó de ID $current_active_id a $new_active_id(`$active_key_name`).";
                    }else{
                        $response = "🗑️ Key eliminada: *$key_name*";
                    }
                }else{
                    $response = "🗑️ Key eliminada: *$key_name*";
                }
            }

            sendMessage($response);
        }else{
            sendMessage("❌ No existe una key con ID `$key_id`\.
Usa /mykeys$bot_mention para ver tus keys\.");
        }
    }else{
        sendMessage("❌ Uso: /deletekey$bot_mention \[ID\]
_Usa /mykeys para ver tus keys\._");
    }
    exit;
}
// /editkey
elseif(strpos($user_text, "/editkey$bot_mention") === 0){
    if($is_group){
        sendReply($message_id, $MDONLY_MSG);
        exit;
    }

    $lines = explode("
", trim($user_text));
    if(count($lines) >= 2){
        $parts = explode(" ", $lines[0]);
        $key_id = trim($parts[1]);
        $new_name = trim($lines[1]);
        if(count($lines) === 2){
          $new_key = $old_key;
        }else{
          $new_key = trim($lines[2]);
        }

        // Verificar si existe la key
        if(isset($keys_data[$key_id])){
            $old_name = $keys_data[$key_id]['name'];
            $old_key = OpenSSL_Dec($keys_data[$key_id]['key']);

            // Actualizar los datos
            $keys_data[$key_id] = ["name" => $new_name, "key" => OpenSSL_Enc($new_key)];

            file_put_contents($keys_file, json_encode($keys_data, JSON_PRETTY_PRINT));

            // Verificar si era la key activa
            $active_key_file = "../users/$user_id/$SHAPE_USERNAME.txt";
            $is_active = file_exists($active_key_file) && file_get_contents($active_key_file) == $key_id;

            $response = "✅ Key *$key_id* actualizada:
Antiguo nombre: `$old_name`
Antigua API Key: `$old_key`
Nuevo nombre: `$new_name`
Nueva API Key: `$new_key`";

            if($is_active){
                $response .= "

ℹ️ Esta key está actualmente activa\.";
            }

            sendMessage($response);
        }else{
            sendMessage("❌ No existe una key con ID `$key_id`\.
Usa /mykeys para ver tus keys\.");
        }
    }else{
        sendMessage("❌ Formato incorrecto\. Usa:
/editkey \[ID\]
\[Nuevo nombre\]
\[Nueva API Key\]");
    }
    exit;
}

// Comandos estúpidos
global $command_response;
$toCoMsg = explode(" ", $user_text);

if($SHAPE_COMMAND_DICE==true && $toCoMsg[0]=="/dice$bot_mention"){
  $dice = rand(1, 6);
  $command_response = $is_group?"¡`$user_name` lanzó un *$dice*\!":"¡Lanzaste un *$dice*\!";
}elseif($SHAPE_COMMAND_8BALL==true && $toCoMsg[0]=="/8ball$bot_mention"){
  $command_response = $user_text;
  include "../Commands/8ball.php";
}
if($command_response!=""){
  $is_group?sendReply($message_id, $command_response):sendMessage($command_response);
  exit;
}

$image_url = null;
$audio_url = null;

$is_doc = isset($message['document']);
$doc_txt = "";

if(isset($message['voice'])){
    $file_id = $message['voice']['file_id'];
    $audio_url = getTelegramFileUrl($file_id);
}elseif(($is_doc && strpos($message['document']['mime_type'], "audio")!== false) || isset($message['audio'])){
    $file_id = $message['document']['file_id'] ?? $message['audio']['file_id'];
    $audio_url = getTelegramFileUrl($file_id);
}elseif($is_doc){
  $file_id = $message['document']['file_id'];
  $file_name = $message['document']['file_name'] ?? 'file.txt';
  $file_size = $message['document']['file_size'];
  $file_type = $message['document']['mime_type'];
  $is_pdf = (stripos($file_name, "pdf")!== false);
  if((!$is_pdf && $file_size>(1024*$MAXDOC_SIZE*4)) || $file_size>(1024*1024*$MAXPDF_SIZE*4)){
  $doc_txt = '(Archivo \"'.$file_name.'\" demasiado grande - '.($file_size/1024/1024).' KiB)

';
}else{
  $doc_url = getTelegramFileUrl($file_id);
  $doc_ext = (stripos($file_type, "json")!== false)?'json':($is_pdf?'pdf':'plaintext');
  $doc_dlc = @file_get_contents($doc_url);
  if($is_pdf){
    require_once "../PDFAL.php-dist";
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseContent($doc_dlc);
    $doc_dlc = $pdf->getText();
    $doc_len = strlen($doc_dlc);
    if($doc_len> 1024*15){
    $doc_txt = '(Archivo \"'.$file_name.'\" demasiado grande - '.$doc_len.' caracteres)

';
}
  }
  if(!empty($doc_dlc)) $doc_txt = '```'.$doc_ext.' -(Contenido del archivo \"'.$file_name.'\")
'.str_replace("\"", "\\\"", $doc_dlc).'
```

';
}
}elseif(isset($message['photo']) || ($VIEWSTICKERS==true && !empty($sticker['thumb']['file_id']))){
    if(isset($message['photo'])) $photo = end($message['photo']);
    $file_id = $photo['file_id'] ?? $sticker['thumb']['file_id'];
    $image_url = getTelegramFileUrl($file_id);
}

if(!empty($doc_ext)) $user_text = "$doc_txt$user_text";

$should_respond = false;
$clean_text = str_replace(["!reset", "!wack", "!imagine", "!info", "!web", "!sleep", "!help", "!dashboard"], ["\!\\reset", "\!\\wack", "\!\\imagine", "\!\\info", "\!\\web", "\!\\sleep", "\!\\help", "\!\\dashboard"], trim($user_text));

$user_context = '
(["platform": "telegram", "';

if($is_private){
    $should_respond = true;
    $user_context .= 'chat": "private';
}elseif($is_group){
    $is_mentioned = strpos($user_text, "@$BOT_USERNAME")!== false || strpos(strtolower($user_text), strtolower($SHAPE_NAME)) && $is_free;
    if(!$is_mentioned && !empty($Favorite_words) && is_array($Favorite_words)){
      foreach($Favorite_words as $word){
    if(preg_match("/\b".preg_quote($word, '/')."\b/i", $user_text)){
        $is_called = 1;
        break;
    }
}
    }
    $is_active = file_exists($group_file);
    $should_respond = ((rand(1, $ANSWER_PROB)===1 && $is_free) || $is_active || $is_reply_to_bot || $is_mentioned || ($is_called && $is_free));
    if(!empty($replying_to_user)){
        $user_context .= $user_name.' replying to '.$replying_to_user.'", "group": "'.$message["chat"]["title"];
    }else{
      $user_context .= 'group": '.$message['chat']['title'];
    }
}
$user_context .= '", "{user}": "'.$user_name.'"])';

$OReacts = [
"Hol" => ["👊", "👻", "🥱", "👀", "🤖", "🔥", "🙏", "🎉", "🎊", "🍑"],
"Hi" => ["👊", "👻", "🥱", "👀", "🤖", "🔥", "🙏", "🎉", "🎊", "🍑"],
"Hel" => ["👊", "👻", "🥱", "👀", "🤖", "🔥", "🙏", "🎉", "🎊", "🍑"],
"Adi" => ["🗿", "🆒", "💩", "👍", "💯", "💔", "👊"],
"Bye" => ["🗿", "🆒", "💩", "👍", "💯", "💔", "👊"],
$SHAPE_NAME => ["🔥", "👻"]
];

// Reacción
$Reacts = isset($Reactions)?array_merge($Reactions, $OReacts):$OReacts;

$foundReactions = ["🔥", "❤️", "🥱", "🗿", "👻", "👀"];
foreach($Reacts as $word => $emojis){
  if(stripos($user_text, $word)!== false){
    $foundReactions = array_merge($foundReactions, $emojis);
  }
}
if(!empty($foundReactions)){
  $randomReaction = $foundReactions[array_rand($foundReactions)];
}

if($should_respond && (!empty($clean_text) || !empty($image_url) || !empty($audio_url))){
  if($is_free && $is_group && rand(1,$REACT_PROB1) === 1) setMessageReaction($randomReaction);
    $enhanced_text = $user_context."

".$clean_text;
    $response = call_shapes_api_with_queue($enhanced_text, $SHAPES_API_KEY, $SHAPE_USERNAME, $image_url, $audio_url);
    $new_response = str_replace(["$SHAPE_NAME:", $bot_mention], ["", "@$SHAPE_NAME"],(strpos($response, $user_text) === 0?str_replace(trim($user_text), "", trim($response)):$response));
    $nr_response = strpos($new_response, "\([\"")?preg_replace('/\([^)]*\)/', '', $new_response, 1):$new_response;
    $formatted_response = formatForTelegram($nr_response);

    if(!empty($formatted_response) && $formatted_response != $SHAPE_NAME){
        // Detectar todos los enlaces multimedia
        preg_match_all('/(https?:\/\/[^\s]+\.(?:jpg|jpeg|png|gif|mp4|webm|mp3))/i', $new_response, $media_matches);
        $media_urls = $media_matches[0] ?? [];
        $caption = formatForTelegram(trim(preg_replace('/(https?:\/\/[^\s]+\.(?:jpg|jpeg|png|gif|mp4|webm|mp3))/i', '', $new_response)));

if(!empty($media_urls)){
    $photos=[];$audios=[];
    foreach($media_urls as $url){
        $ext=strtolower(pathinfo($url,PATHINFO_EXTENSION));
        if(in_array($ext,['jpg','jpeg','png','gif','webp'])) $photos[]=$url;
        elseif($ext==='mp3') $audios[]=$url;
    }

    if(!empty($photos)){
      sendChatAction('upload_photo');
        if(count($photos) > 1){
            $media=[];
            foreach($photos as $index=>$photo_url){
                $item=['type'=>'photo','media'=>$photo_url];
                if($index===count($photos)-1 && !empty($caption)){
                    $item['caption']=$caption;
                    $item['parse_mode']='MarkdownV2';
                }
                $media[]=$item;
            }
            $url="https://api.telegram.org/bot$TELEGRAM_TOKEN/sendMediaGroup";
            $post=['chat_id'=>$chat_id,'media'=>json_encode($media)];
            if($is_group) $post['reply_to_message_id']=$message_id;
            $ch=curl_init($url);
            curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$post,CURLOPT_RETURNTRANSFER=>1]);
            curl_exec($ch);
            curl_close($ch);
        }else{
            $url="https://api.telegram.org/bot$TELEGRAM_TOKEN/sendPhoto";
            $post=['chat_id'=>$chat_id,'photo'=>$photos[0],'caption'=>$caption,'parse_mode'=>'MarkdownV2'];
            if($is_group) $post['reply_to_message_id']=$message_id;
            $ch=curl_init($url);
            curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$post,CURLOPT_RETURNTRANSFER=>1]);
            curl_exec($ch);
            curl_close($ch);
        }
        usleep(500000);
    }

    if(!empty($audios)){
      sendChatAction('upload_audio');
        foreach($audios as $audio_url){
            $url="https://api.telegram.org/bot$TELEGRAM_TOKEN/sendAudio";
            $post=['chat_id'=>$chat_id,'audio'=>$audio_url];
            if(empty($photos) && !empty($caption)){
                $post['caption']=$caption;
                $post['parse_mode']='MarkdownV2';
            }
            if($is_group) $post['reply_to_message_id']=$message_id;
            $ch=curl_init($url);
            curl_setopt_array($ch,[CURLOPT_POST=>1,CURLOPT_POSTFIELDS=>$post,CURLOPT_RETURNTRANSFER=>1]);
            curl_exec($ch);
            curl_close($ch);
            usleep(500000);
        }
    }

    if(empty($photos) && empty($audios) && !empty($formatted_response)){
        $is_group?sendReply($message_id,$formatted_response):sendMessage($formatted_response);
    }
}else{
    $is_group?sendReply($message_id,$formatted_response):sendMessage($formatted_response);
}
    }else{
        $fallback_msg = $ERROR_MSG;
        $is_group?sendReply($message_id, $fallback_msg):sendMessage($fallback_msg);
    }
}elseif($is_group && $is_free && rand(1,$REACT_PROB2) === 1){
  setMessageReaction($randomReaction);
}
exit; // Por si acaso...
?>