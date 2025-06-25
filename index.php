<?php
session_start();
$default_events=[
'message'=>true,
'edited_message'=>false,
'channel_post'=>false,
'edited_channel_post'=>false,
'inline_query'=>false,
'chosen_inline_result'=>false,
'callback_query'=>true,
'shipping_query'=>false,
'pre_checkout_query'=>false,
'poll'=>false,
'poll_answer'=>false,
'my_chat_member'=>false,
'chat_member'=>false,
'chat_join_request'=>false
];
$saved_config=$_SESSION['webhook_config']??[
'url'=>'',
'events'=>$default_events,
'token'=>''
];
$feedback='';
$token=$_SESSION['webhook_config']['token']??'';
if($_SERVER['REQUEST_METHOD']==='POST'){
$token=trim($_POST['token']??'');
$webhook_url=trim($_POST['webhook_url']??'');
$action=$_POST['action']??'configure';
if(!empty($token)){
if($action==='delete'){
$result=file_get_contents("https://api.telegram.org/bot$token/deleteWebhook");
$feedback="<div class='info'>馃棏锔� Webhook eliminado:<br><pre>".htmlspecialchars($result)."</pre></div>";
}elseif(!empty($webhook_url)){
if(!preg_match('/^https:\/\//i',$webhook_url)){
$feedback="<div class='error'>鈿狅笍 La URL del webhook debe usar HTTPS</div>";
header("Location:./");
exit();
}
$events=[];
foreach($default_events as $event=>$default){
$events[$event]=isset($_POST['events'][$event]);
}
$active_events=array_keys(array_filter($events));
file_get_contents("https://api.telegram.org/bot$token/deleteWebhook");
$api_url="https://api.telegram.org/bot$token/setWebhook?".http_build_query([
'url'=>$webhook_url,
'max_connections'=>100,
'allowed_updates'=>json_encode($active_events),
'drop_pending_updates'=>true
]);
$result=file_get_contents($api_url);
$_SESSION['webhook_config']=[
'url'=>$webhook_url,
'events'=>$events,
'token'=>$token
];
$feedback="<div class='success'>馃攧 Webhook actualizado!<br>Eventos activos: "
.implode(', ',$active_events)
."<br><pre>".htmlspecialchars($result)."</pre></div>";
}
}else{
$feedback="<div class='error'>鈿狅笍 Se requiere el token del bot</div>";
}
header("Location:./");
exit();
}else{
$events=$saved_config['events']??$default_events;
$token=$_SESSION['webhook_config']['token']??'';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configurador Avanzado de Webhook</title>
<style>
body{font-family:Arial,sans-serif;max-width:700px;margin:20px auto;padding:20px;}
.form-group{margin-bottom:15px;}
label{display:block;margin-bottom:5px;font-weight:bold;}
input[type="text"]{width:100%;padding:8px;box-sizing:border-box;}
button{background:#0088cc;color:white;border:none;padding:10px 15px;cursor:pointer;margin-right:10px;}
button:hover{background:#006699;}
.btn-delete{background:#e74c3c;}
.btn-delete:hover{background:#c0392b;}
.success{color:#2ecc71;margin:15px 0;}
.error{color:#e74c3c;margin:15px 0;}
.info{color:#3498db;margin:15px 0;}
pre{background:#f5f5f5;padding:10px;overflow-x:auto;}
.events-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;}
.event-option{display:flex;align-items:center;}
.event-option input{margin-right:8px;}
.button-group{margin:20px 0;}
</style>
</head>
<body>
<h1>Configurador de Webhook para Telegram</h1>
<?php if(!empty($feedback))echo $feedback;?>
<form method="POST" action="<?=htmlspecialchars($_SERVER['PHP_SELF'])?>">
<div class="form-group">
<label for="token">Token del bot:</label>
<input type="text" id="token" name="token" required placeholder="Ej:123456:ABC-DEF1234ghIkl" value="<?=htmlspecialchars($token)?>">
</div>
<div class="form-group">
<label for="webhook_url">URL del webhook (HTTPS):</label>
<input type="text" id="webhook_url" name="webhook_url" value="<?=htmlspecialchars($saved_config['url']??'')?>" required placeholder="Ej:https://tudominio.com/bots.php">
</div>
<div class="form-group">
<label>Eventos a recibir:</label>
<div class="events-grid">
<?php foreach($default_events as $event=>$default):?>
<div class="event-option">
<input type="checkbox" id="event_<?=$event?>" name="events[<?=$event?>]" <?=($events[$event]??$default)?'checked':''?>>
<label for="event_<?=$event?>">
<?=match($event){
'message'=>'Todos los mensajes (incluye voz/audio)',
'callback_query'=>'Botones interactivos',
'edited_message'=>'Mensajes editados',
'channel_post'=>'Publicaciones en canal',
'edited_channel_post'=>'Publicaciones editadas en canal',
'inline_query'=>'Consultas inline',
'chosen_inline_result'=>'Resultados inline seleccionados',
'shipping_query'=>'Consultas de env铆o',
'pre_checkout_query'=>'Consultas pre-pago',
'poll'=>'Encuestas',
'poll_answer'=>'Respuestas a encuestas',
'my_chat_member'=>'Actualizaciones de mi estado en chat',
'chat_member'=>'Actualizaciones de miembros',
'chat_join_request'=>'Solicitudes de uni贸n a chat',
default=>$event
}?>
</label>
</div>
<?php endforeach;?>
</div>
</div>
<div class="button-group">
<button type="submit" name="action" value="configure">馃捑 Configurar Webhook</button>
<button type="submit" name="action" value="delete" class="btn-delete">馃棏锔� Eliminar Webhook</button>
</div>
</form>
<?php if(!empty($saved_config['url'])):?>
<div class="info">
<h3>Configuraci贸n actual:</h3>
<p><strong>URL:</strong> <?=htmlspecialchars($saved_config['url'])?></p>
<p><strong>Eventos activos:</strong> <?=implode(', ',array_keys(array_filter($saved_config['events'])))?></p>
</div>
<?php endif;?>
</body>
</html>