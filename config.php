<?php
// Tu contraseña secreta para añadir comandos.
$SITE_PASS = 'TU_CONTRASEÑA';

// Tu clave secreta para guardar tokens.
$ENC_KEY = 'TU_KEY_SECRETA';

// Mensaje del comando /help (soporta las variables {shape} y {user})
$HELP_CMD = "*Comandos de {shape}*:

/start - Verifica si {shape} está en línea.
/help - Información.
/activate - Activa a {shape} en este grupo.
/deactivate - Desactiva a {shape} en este grupo.

/register - Registra una de tus Shape Keys para evitar el Error 429 (Demasiadas solicitudes) *(MD)*
/mykeys - Muestra tus Shape Keys registradas. *(MD)*
/setkey - Haz que {shape} use una de tus Shape Keys en respuestas para ti.
/editkey - Edita una de tus Shape Keys registradas.
/deletekey - Elimina una de tus Shape Keys registradas.

/freewill - Deja que {shape} reaccione y responda cuando quiera.
/web - Haz que {shape} pueda buscar en la web y responderte con datos actualizados.
/wack - Borra la memoria a corto plazo de {shape}.
/reset - Borra la memoria a largo plazo de {shape}.
/sleep - Genera una nueva memoria a largo plazo.
/imagine - Genera una imagen con IA.

/http - Dale una URL a {shape} para que la escanee.";

// Mensaje inicial predeterminado.
$DEFSTART_MSG = "✅";

// Mensaje de error predeterminado.
$DEFERROR_MSG = "Eh?";

// Mensajes de activado y desactivado predeterminados.
$DEFACTIVATE_MSG = "✅";
$DEFDEACTIVATE_MSG = "❌";

// Para comandos (se deben escapar caracteres especiales).
$MDONLY_MSG = "❌ Este comando solo se puede usar en MD\.";
$ADMINSONLY_MSG = "❌ Solo Admins";

// Tamaño máximo de documentos en KiB (ten en cuenta que esto llena el contexto del modelo)
$MAX_DOCSIZE = 10;

// Tamaño máximo de PDFs a procesar en MiB (este no llenaría tanto el contexto del modelo, ya que solo se extrae el texto)
$MAX_PDFSIZE = 100;

// Free-will:
// Probabilidad de que el bot añada una reacción a mensajes dirijidos a este (actual: 1/15)
$REACT_PROB1 = 15;

// Probabilidad de que añada una reacción a mensajes no dirijidos a este (actual: 1/40)
$REACT_PROB2 = 40;
?>