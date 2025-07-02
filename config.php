<?php
// Tu contraseña secreta para añadir comandos.
$SITE_PASS = 'TU_CONTRASEÑA';
// Tamaño máximo de documentos en KiB (ten en cuenta que esto llena el contexto del modelo, aparte de que hay un límite de caracteres puesto por Shapes, Inc. API)
$MAX_DOCSIZE = 15;
// Tamaño máximo de PDFs a procesar en MiB (este no llenaría tanto el contexto del modelo, ya que solo se extrae el texto)
$MAX_PDFSIZE = 100;
// Probabilidad de que el bot añada una reacción a mensajes dirijidos a este (actual: 1/30)
$REACT_PROB1 = 30;
// Probabilidad de que añada una reacción a mensajes no dirijidos a este (actual: 1/80)
$REACT_PROB2 = 80;
?>
