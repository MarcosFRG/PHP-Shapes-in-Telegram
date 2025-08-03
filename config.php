<?php
// Tu contraseña secreta para añadir comandos.
$SITE_PASS = 'TU_CONTRASEÑA';

// Tu clave secreta para guardar Shape Keys.
$ENC_KEY = 'TU_CLAVE_SECRETA';

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

// Probabilidad de que el bot añada una reacción a mensajes dirijidos a este (actual: 1/15)
$REACT_PROB1 = 15;

// Probabilidad de que añada una reacción a mensajes no dirijidos a este (actual: 1/40)
$REACT_PROB2 = 40;

?>
