<?php
session_start();

require_once "config.php";

// Contraseña de acceso
define('PASSWORD', $SITE_PASS);

// Verificar contraseña
if(!isset($_SESSION['authenticated'])){
    if(isset($_POST['password'])){
        if($_POST['password'] === PASSWORD){
            $_SESSION['authenticated'] = true;
        }else{
            $error = "Contraseña incorrecta";
        }
    }
    // Mostrar formulario de login si no está autenticado
    if(!isset($_SESSION['authenticated'])){
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Acceso</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .login-box { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 300px; text-align: center; }
                input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
                button { background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; }
                .error { color: red; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h2>Acceso</h2>
                '.(isset($error) ? '<p class="error">'.$error.'</p>' : '').'
                <form method="post">
                    <input type="password" name="password" placeholder="Contraseña" required>
                    <button type="submit">Entrar</button>
                </form>
            </div>
        </body>
        </html>';
        exit;
    }
}

// Inicializar variables
$success = '';
$commands = [];
$bots = [];

// Procesar POST (añadir/eliminar/guardar/ejecutar comandos)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['authenticated'])){
    $commandsFile = 'commands.json';
    $commands = file_exists($commandsFile) ? json_decode(file_get_contents($commandsFile), true) : [];
    if($commands === null){
        $commands = [];
    }

    // Añadir comando
    if(isset($_POST['add_command']) && !empty($_POST['command_name']) && !empty($_POST['command_description'])){
        $newCommand = [
            'name' => $_POST['command_name'],
            'description' => $_POST['command_description'],
            'variable' => $_POST['command_variable'] ?? ''
        ];
        $commands[] = $newCommand;
        file_put_contents($commandsFile, json_encode($commands, JSON_PRETTY_PRINT));
    }

    // Editar comando
    if(isset($_POST['edit_command']) && isset($_POST['edit_index']) && !empty($_POST['command_name']) && !empty($_POST['command_description'])){
        $index = (int)$_POST['edit_index'];
        if(isset($commands[$index])){
            $commands[$index] = [
                'name' => $_POST['command_name'],
                'description' => $_POST['command_description'],
                'variable' => $_POST['command_variable'] ?? ''
            ];
            file_put_contents($commandsFile, json_encode($commands, JSON_PRETTY_PRINT));
        }
    }

    // Eliminar comando
    if(isset($_POST['delete_command'])){
        $index = (int)$_POST['delete_command'];
        if(isset($commands[$index])){
            array_splice($commands, $index, 1);
            file_put_contents($commandsFile, json_encode($commands, JSON_PRETTY_PRINT));
        }
    }

    // Mover comando arriba
    if(isset($_POST['move_up'])){
        $index = (int)$_POST['move_up'];
        if($index > 0 && isset($commands[$index])){
            $temp = $commands[$index - 1];
            $commands[$index - 1] = $commands[$index];
            $commands[$index] = $temp;
            file_put_contents($commandsFile, json_encode($commands, JSON_PRETTY_PRINT));
        }
    }

    // Mover comando abajo
    if(isset($_POST['move_down'])){
        $index = (int)$_POST['move_down'];
        if($index < count($commands) - 1 && isset($commands[$index])){
            $temp = $commands[$index + 1];
            $commands[$index + 1] = $commands[$index];
            $commands[$index] = $temp;
            file_put_contents($commandsFile, json_encode($commands, JSON_PRETTY_PRINT));
        }
    }

    // Ejecutar comandos en los bots
    if(isset($_POST['execute_commands'])){
        // Escanear directorios
        $directories = array_filter(glob('*'), 'is_dir');
        $directories = array_diff($directories, ['Commands', 'users', 'PDFParser']);

        foreach($directories as $dir){
            $botFile = $dir.'/bot.php';
            if(file_exists($botFile)){
                // Extraer token y variables del bot
                $content = file_get_contents($botFile);
                preg_match('/\$TELEGRAM_TOKEN\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $tokenMatch);

                if(!empty($tokenMatch[1])){
                    $token = $tokenMatch[1];
                    $botCommands = [];

                    // Obtener $SHAPE_NAME si existe directamente
                    preg_match('/\$SHAPE_NAME\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $shapeNameMatch);
                    $shapeName = !empty($shapeNameMatch[1]) ? $shapeNameMatch[1] : '';

                    // Si no existe SHAPE_NAME, obtener SHAPE_USERNAME y consultar la API
                    if(empty($shapeName)){
                        preg_match('/\$SHAPE_USERNAME\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $shapeUsernameMatch);
                        if(!empty($shapeUsernameMatch[1])){
                            $shapeUsername = $shapeUsernameMatch[1];
                            $shapeInfo = json_decode(file_get_contents("https://api.shapes.inc/shapes/public/$shapeUsername"), true);
                            if($shapeInfo && isset($shapeInfo["name"])){
                                $shapeName = $shapeInfo["name"];
                            }
                        }
                    }

                    foreach($commands as $cmd){
                        // Verificar si el comando requiere una variable especial
                        if(!empty($cmd['variable'])){
                            preg_match('/\$'.preg_quote($cmd['variable']).'\s*=\s*(?:[\'"]([^\'"]+)[\'"]|true|false|\d+)/', $content, $varMatch);
                            if(empty($varMatch)) continue;
                        }

                        // Reemplazar {shape} en nombre y descripción
                        $commandName = str_replace('{shape}', $shapeName, $cmd['name']);
                        $commandDescription = str_replace('{shape}', $shapeName, $cmd['description']);

                        $botCommands[] = [
                            'command' => $commandName,
                            'description' => $commandDescription
                        ];
                    }

                    // Enviar comandos a la API de Telegram
                    if(!empty($botCommands)){
                        $apiUrl = "https://api.telegram.org/bot{$token}/setMyCommands";

                        $ch = curl_init($apiUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['commands' => $botCommands]));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        $response = curl_exec($ch);
                        curl_close($ch);
                    }
                }
            }
        }
        $success = "Comandos ejecutados en todos los bots";
    }

    // Redirigir para evitar reenvío del formulario
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Cargar comandos existentes
$commandsFile = 'commands.json';
$commands = file_exists($commandsFile) ? json_decode(file_get_contents($commandsFile), true) : [];
if($commands === null){
    $commands = [];
}

// Escanear directorios para listar bots
$directories = array_filter(glob('*'), 'is_dir');
$directories = array_diff($directories, ['Commands', 'users']);
$bots = [];
foreach($directories as $dir){
    $botFile = $dir.'/bot.php';
    if(file_exists($botFile)){
        $content = file_get_contents($botFile);
        preg_match('/\$TELEGRAM_TOKEN\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $tokenMatch);
        if(!empty($tokenMatch[1])){
            // Obtener SHAPE_NAME directamente o a través de SHAPE_USERNAME
            preg_match('/\$SHAPE_NAME\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $shapeNameMatch);
            $shapeName = !empty($shapeNameMatch[1]) ? $shapeNameMatch[1] : '';

            if(empty($shapeName)){
                preg_match('/\$SHAPE_USERNAME\s*=\s*[\'"]([^\'"]+)[\'"]/', $content, $shapeUsernameMatch);
                if(!empty($shapeUsernameMatch[1])){
                    $shapeUsername = $shapeUsernameMatch[1];
                    $shapeInfo = json_decode(file_get_contents("https://api.shapes.inc/shapes/public/$shapeUsername"), true);
                    $shapeName = $shapeInfo && isset($shapeInfo["name"]) ? $shapeInfo["name"] : 'Shape';
                }else{
                    $shapeName = 'Shape';
                }
            }

            $bots[] = [
                'name' => $dir,
                'token' => $tokenMatch[1],
                'shape_name' => $shapeName
            ];
        }
    }
}

// Verificar si estamos editando un comando
$editing = false;
$editIndex = null;
$editCommand = null;
if(isset($_GET['edit'])){
    $editIndex = (int)$_GET['edit'];
    if(isset($commands[$editIndex])){
        $editing = true;
        $editCommand = $commands[$editIndex];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gestor de Comandos para Bots</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: #0056b3; color: white; padding: 20px; border-radius: 8px 8px 0 0; margin-bottom: 20px; }
        .panel { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        tr:hover { background-color: #f5f5f5; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button, .btn { background: #0056b3; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        button:hover, .btn:hover { background: #003d7a; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #a71d2a; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #117a8b; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; }
        .flex { display: flex; gap: 10px; }
        .shape-badge { background: #6c757d; color: white; padding: 3px 8px; border-radius: 10px; font-size: 0.8em; }
        .action-buttons { display: flex; gap: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Gestor de Comandos para Bots de Telegram</h1>
            <p>Los comandos con {shape} serán reemplazados por el nombre de la forma de cada bot</p>
        </div>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="panel">
            <h2>Bots Detectados</h2>
            <table>
                <thead>
                    <tr>
                        <th>Carpeta</th>
                        <th>Token</th>
                        <th>SHAPE_NAME</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bots as $bot): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bot['name']); ?></td>
                            <td><?php echo htmlspecialchars(substr($bot['token'], 0, 10).'...'); ?></td>
                            <td><span class="shape-badge"><?php echo htmlspecialchars($bot['shape_name']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="panel">
            <h2>Comandos Actuales</h2>
            <form method="post">
                <table>
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Comando</th>
                            <th>Descripción</th>
                            <th>Variable Especial</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($commands as $index => $cmd): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>/<?php echo htmlspecialchars($cmd['name']); ?></td>
                                <td><?php echo htmlspecialchars($cmd['description']); ?></td>
                                <td><?php echo !empty($cmd['variable']) ? '$'.htmlspecialchars($cmd['variable']) : '-'; ?></td>
                                <td class="action-buttons">
                                    <a href="?edit=<?php echo $index; ?>" class="btn btn-info">Editar</a>
                                    <button type="submit" name="delete_command" value="<?php echo $index; ?>" class="btn-danger">Eliminar</button>
                                    <button type="submit" name="move_up" value="<?php echo $index; ?>" class="btn" <?php echo $index == 0 ? 'disabled' : ''; ?>>↑</button>
                                    <button type="submit" name="move_down" value="<?php echo $index; ?>" class="btn" <?php echo $index == count($commands) - 1 ? 'disabled' : ''; ?>>↓</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($commands)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No hay comandos definidos</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        <div class="panel">
            <h2><?php echo $editing ? 'Editar Comando' : 'Añadir Nuevo Comando'; ?></h2>
            <form method="post">
                <?php if($editing): ?>
                    <input type="hidden" name="edit_index" value="<?php echo $editIndex; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="command_name">Nombre del Comando (sin "/")</label>
                    <input type="text" id="command_name" name="command_name" required 
                           value="<?php echo $editing ? htmlspecialchars($editCommand['name']) : ''; ?>" 
                           placeholder="Ej: dice_{shape}">
                    <small>Usa {shape} para que se reemplace por el SHAPE_NAME del bot</small>
                </div>
                <div class="form-group">
                    <label for="command_description">Descripción</label>
                    <input type="text" id="command_description" name="command_description" required 
                           value="<?php echo $editing ? htmlspecialchars($editCommand['description']) : ''; ?>" 
                           placeholder="Ej: Lanza un dado {shape}">
                    <small>Usa {shape} para que se reemplace por el nombre de la shape</small>
                </div>
                <div class="form-group">
                    <label for="command_variable">Variable Especial (opcional, ej: "SHAPE_COMMAND_8BALL")</label>
                    <input type="text" id="command_variable" name="command_variable" 
                           value="<?php echo $editing ? htmlspecialchars($editCommand['variable']) : ''; ?>" 
                           placeholder="Dejar vacío si no es necesario">
                    <small>Solo se aplicará si el bot tiene esta variable definida</small>
                </div>
                <?php if($editing): ?>
                    <button type="submit" name="edit_command" class="btn-success">Guardar Cambios</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Cancelar</a>
                <?php else: ?>
                    <button type="submit" name="add_command" class="btn-success">Añadir Comando</button>
                <?php endif; ?>
            </form>
        </div>
        <div class="panel">
            <h2>Acciones</h2>
            <form method="post">
                <div class="flex">
                    <button type="submit" name="execute_commands" class="btn-success">Ejecutar Comandos en Todos los Bots</button>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Recargar</a>
                </div>
            </form>
            <p><small>Al ejecutar: {shape} será reemplazado por el SHAPE_NAME de cada bot</small></p>
        </div>
    </div>
</body>
</html>