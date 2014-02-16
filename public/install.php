<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.12.1
 * @package     Boeke
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

// Constante necesaria para que el config.php ignore ciertas directivas
// y no se comporte como si lo incluyese la aplicación.
define('INSTALLING', true);

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR
    . 'Boeke' . DIRECTORY_SEPARATOR . 'config.php';

if (@$config['debug']) {
    error_reporting(E_ALL);
}

/**
 * Devuelve el contenido de una variable pasada por POST o un valor por
 * defecto en caso de no estar definida.
 * 
 * @param string $name Nombre del parámetro.
 * @param mixed $default Valor por defecto.
 * @return mixed Valor por defecto o del parámetro.
 */
function requestVar($name, $default = '')
{
    return (isset($_POST[$name])) ? $_POST[$name] : $default;
}

/**
 * Devuelve un array con las consultas en un archivo SQL.
 * @param string $file Ruta al archivo.
 * @return array
 */
function splitSqlFile($file)
{
    $content = preg_replace('/  /', ' ', file_get_contents($file));

    // Split the content by newline character and filter empty lines
    // and SQL comments
    $lines = array_filter(explode("\n", $content), function ($item) {
        if (empty($item)) {
            return false;
        } elseif ($item[0] == '-' || $item[0] == '#') {
            return false;
        } else {
            return true;
        }
    });

    $queries = array();
    $query = '';
    foreach ($lines as $line) {
        $query .= $line;
        // If line contains ; it means it's the last line of the query
        if (strpos($line, ';') !== false) {
            $queries[] = $query;
            $query = '';
        }
    }

    return $queries;
}

// Mensajes de error separados por tipo para ser mostrados en sus secciones
$messages = array(
    'database'  => array(),
    'cookie'    => array(),
    'general'   => array(),
    'admin'     => array(),
);

// Mensajes de error para cada campo inválido
$errors = array(
    'database_host'             => 'El servidor de la base de datos es obligatorio.',
    'database_user'             => 'El usuario de la base de datos es obligatorio.',
    'database_port'             => 'El puerto de la base de datos es obligatorio.',
    'database_pass'             => 'La contraseña del usuario de la base de datos es obligatorio.',
    'database_name'             => 'El nombre de la base de datos es obligatorio.',
    
    'admin_username'            => 'El nombre de usuario debe tener entre 5 y 60 caracteres.',
    'admin_full_name'           => 'El nombre completo debe tener entre 3 y 90 caracteres.',
    'admin_password'            => 'La contraseña debe tener entre 5 y 60 caracteres.',
    'admin_password_repeat'     => 'Las contraseñas no coinciden.',
);

$post = false;
$success = false;

// ¿Existe el archivo de configuración?
if (file_exists(dirname(dirname(__FILE__)) . DSEP . 'config.yml')) {
    // Si el archivo de configuración existe se mostrará un mensaje y se abortará la instalación
    $messages['database'][] = 'Ya existe un archivo de configuración <strong>config.yml</strong>. Se abortará la instalación.';
} else {
    // Si el método es POST se procesarán los datos
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $post = true;
        
        // Recogemos los datos
        $fields = array(
            'database'      => array(
                'host'          => requestVar('db_server'),
                'user'          => requestVar('db_user'),
                'port'          => (int)requestVar('db_port'),
                'pass'          => requestVar('db_pass'),
                'name'          => requestVar('db_name'),
            ),
            'cookie'        => array(
                'path'          => substr(
                    $_SERVER['SCRIPT_NAME'],
                    0,
                    strpos($_SERVER['SCRIPT_NAME'], 'install.php')
                ),
                'secure'        => !empty($_SERVER['HTTPS']),
                'http_only'     => true,
                'lifetime'      => '15 days',
                'name'          => 'boeke_session_' . uniqid(),
            ),
            'general'       => array(
                'secret_key'            => sha1(uniqid()),
                'login_max_retries'     => 6,
                'login_block'           => 600,
            ),
            'admin'         => array(
                'username'          => requestVar('nombre_usuario'),
                'full_name'         => requestVar('nombre_completo'),
                'password'          => requestVar('usuario_pass'),
                'password_repeat'   => requestVar('password_repeat')
            ),
        );
            
        // Validamos los diferentes campos
        foreach ($fields as $type => $content) {
            if (in_array($type, array('general', 'cookie'))) {
                continue;
            }

            foreach ($content as $key => $value) {
                $fieldName = $type . '_' . $key;
                if (is_int($value)) {
                    if ($value < 1) {
                        $messages[$type][] = $errors[$fieldName];
                    }
                } elseif (is_string($value)) {
                    $error = false;
                    if ($fieldName == 'admin_password_repeat') {
                        if ($value != $fields['admin']['password']) {
                            $error = true;
                        }
                    } elseif ($fieldName == 'admin_username'
                        || $fieldName == 'admin_password') {
                        if (strlen($value) < 5 || strlen($value) > 60) {
                            $error = true;
                        }
                    } elseif ($fieldName == 'admin_full_name') {
                        if (strlen($value) < 3 || strlen($value) > 90) {
                            $error = true;
                        }
                    } elseif (strlen($value) === 0) {
                        $error = true;
                    }
                    
                    if ($error) {
                        $messages[$type][] = $errors[$fieldName];
                    }
                }
            }
        }
        
        // ¿Hay mensajes de error en $messages?
        if (count(array_filter($messages, function ($item) {
            return count($item) > 0;
        })) === 0) {
            $connectionWorks = true;
            
            // Probamos la nueva conexión
            try{
                $dbh = new \PDO(
                    'mysql:host=' .
                    $fields['database']['host'] .
                    ';dbname=' . $fields['database']['name'] .
                    ';port=' . $fields['database']['port'] . ';charset=UTF8',
                    $fields['database']['user'],
                    $fields['database']['pass'],
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );
            }
            catch(\PDOException $e){
                // Si la conexión no funciona se lanzará una excepción y daremos el mensaje de error
                $connectionWorks = false;
                $messages['database'][] = 'Los datos proporcionados no son correctos. No puede establecerse conexión.';
            }
            
            if ($connectionWorks) {
                // Si la conexión funciona probaremos si hay una instalación previa
                try {
                    $stmt = $dbh->prepare('SELECT * FROM usuario LIMIT 1');
                    $stmt->execute();
                    
                    $messages['database'][] = 'Hay una instalación previa en la base de datos. Se abortará la instalación.';
                } catch (\PDOException $e) {
                    $continue = true;
                    // Cargar las tablas de la base de datos
                    $dbh->beginTransaction();
                    try {
                        $queries = splitSqlFile(dirname(dirname(__FILE__)) . DSEP
                            . 'sql' . DSEP . 'schema.sql');
                        foreach ($queries as $query) {
                            $dbh->exec($query);
                        }
                    } catch (\PDOException $e) {
                        $messages['database'][] = 'Error creando las tablas de la base de datos: ' . $e->getMessage();
                        $continue = false;
                        $dbh->rollBack();
                    }
                    
                    // Crear usuario administrador si se pudieron crear las tablas
                    if ($continue) {
                        try {
                            $stmt = $dbh->prepare('INSERT INTO usuario (nombre_usuario, nombre_completo, usuario_pass, es_admin) VALUES (?, ?, ?, 1)');
                            $stmt->execute(array(
                                $fields['admin']['username'],
                                $fields['admin']['full_name'],
                                password_hash(
                                    $fields['admin']['password'],
                                    PASSWORD_BCRYPT
                                ),
                            ));
 
                            $dbh->commit();
                            $success = true;
                        } catch (\PDOException $e) {
                            $messages['database'][] = 'Error al crear el usuario administrador.';
                            $continue = false;
                            $dbh->rollBack();
                        }
                    }
                    
                    // Generar el yml de configuración si la creación del administrador ha sido correcta
                    if ($continue) {
                        $ymlOutputLines = array('debug: false');
                        foreach ($fields as $type => $content) {
                            foreach ($content as $key => $value) {
                                if ($type == 'admin') {
                                    break;
                                }
                            
                                if (is_bool($value)) {
                                    $value = ($value ? 'true' : 'false');
                                }
                            
                                if ($key == 'secret_key' || $key == 'password_salt') {
                                    $value = '"' . $value . '"';
                                }
                            
                                $fieldName = ($type != 'general' ? $type . '_' : '') . $key;
                                $ymlOutputLines[] = $fieldName . ': ' . $value;
                            }
                        }
                    
                        $ymlOutput = join("\n", $ymlOutputLines);
                    
                        // Si se puede escribir en el directorio escribimos directamente el config.yml
                        if (is_writable(dirname(dirname(__FILE__)))) {
                            file_put_contents(
                                dirname(dirname(__FILE__)) . DSEP . 'config.yml',
                                $ymlOutput
                            );
                        } else {
                            // No se puede escribir en el directorio, lo mandamos al usuario para que lo suba
                            header('Content-type: application/octet-stream');
                            header('Content-Disposition: attachment; filename="config.yml"');
                            file_put_contents(
                                'php://output',
                                $ymlOutput
                            );
                            die();
                        }
                    }
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>Instalar Boeke</title>
        <meta name="description" content="Aplicación de gestión del préstamo de libros a alumnos.">
        <meta name="viewport" content="width=device-width">

        <link rel="stylesheet" href="js/vendor/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="js/vendor/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="css/theme.css">
        <link rel="stylesheet" href="css/main.css">
        <link rel="stylesheet" href="js/vendor/selectize/dist/css/selectize.bootstrap3.css">

        <script src="{{ base_url }}js/vendor/modernizr/modernizr.js"></script>
    </head>
    <body>
        <!--[if lt IE 7]>
            <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
        <![endif]-->
            
            <div class="container col-md-8 col-md-offset-2 col-xs-10 col-xs-offset-1">
                <header>
                    <h1>
                        Instalar Boeke
                        <br />
                        <small>Aplicación de gestión del préstamo de libros a alumnos.</small>
                    </h1>
                </header>
                
                <br />
                
                <div class="row">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            La aplicación ha sido instalada correctamente. Puede ver su aplicación haciendo <a href="index.php">click aquí</a>.
                        </div>
                    <?php elseif (!is_writable(dirname(dirname(__FILE__)))): ?>
                        <div class="alert alert-warning">
                            Parece que el directorio <?= dirname(dirname(__FILE__)) ?> no tiene de permisos de escritura. Al finalizar la instalación se te enviará un fichero <strong>config.yml</strong> que deberás subir en ese mismo directorio.
                        </div>
                    <?php endif; ?>
                    <form role="form" class="form-horizontal" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Configuración de la base de datos</h4>
                            </div>
                            <div class="panel-body">
                                <?php if (count($messages['database']) > 0): ?>
                                    <div class="alert alert-danger">
                                        <?= join('<br />', $messages['database']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label for="db_server" class="col-sm-3 control-label">Servidor</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" required="required" class="form-control" id="db_server" name="db_server" placeholder="Servidor de la base de datos" <?php if ($post) { echo 'value="' . $fields['database']['host'] . '"'; } ?>>
                                    </div>
                                </div>
                        
                                <div class="form-group">
                                    <label for="db_port" class="col-sm-3 control-label">Puerto</label>
                                    <div class="col-sm-9">
                                        <input type="number" required="required" class="form-control" id="db_port" name="db_port" placeholder="Puerto de la base de datos" <?php if ($post) { echo 'value="' . $fields['database']['port'] . '"'; } ?>>
                                    </div>
                                    <div class="col-sm-9 col-sm-offset-3">
                                        <small>Por defecto MySQL usa el puerto 3306.</small>
                                    </div>
                                </div>
                        
                                <div class="form-group">
                                    <label for="db_user" class="col-sm-3 control-label">Usuario</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="db_user" name="db_user" placeholder="Usuario de la base de datos" <?php if ($post) { echo 'value="' . $fields['database']['user'] . '"'; } ?>>
                                    </div>
                                </div>
                        
                                <div class="form-group">
                                    <label for="db_pass" class="col-sm-3 control-label">Contraseña</label>
                                    <div class="col-sm-9">
                                        <input type="password" required="required" class="form-control" id="db_pass" name="db_pass" placeholder="Contraseña del usuario de la base de datos" <?php if ($post) { echo 'value="' . $fields['database']['pass'] . '"'; } ?>>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="db_name" class="col-sm-3 control-label">Base de datos</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="db_name" name="db_name" placeholder="Nombre de la base de datos" <?php if ($post) { echo 'value="' . $fields['database']['name'] . '"'; } ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Administrador de la aplicación</h4>
                            </div>
                            <div class="panel-body">
                                <?php if (count($messages['admin']) > 0): ?>
                                    <div class="alert alert-danger">
                                        <?= join('<br />', $messages['admin']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label for="nombre_usuario" class="col-sm-3 control-label">Nombre de usuario</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="nombre_usuario" name="nombre_usuario" placeholder="Nombre de usuario..." <?php if ($post) { echo 'value="' . $fields['admin']['username'] . '"'; } ?>>
                                    </div>
                                </div>
                    
                                <div class="form-group">
                                    <label for="nombre_completo" class="col-sm-3 control-label">Nombre completo</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="nombre_completo" name="nombre_completo" placeholder="Nombre completo..." <?php if ($post) { echo 'value="' . $fields['admin']['full_name'] . '"'; } ?>>
                                    </div>
                                </div>
                    
                                <div class="form-group">
                                    <label for="usuario_pass" class="col-sm-3 control-label">Contraseña</label>
                                    <div class="col-sm-9">
                                        <input type="password" required="required" class="form-control" id="usuario_pass" name="usuario_pass" placeholder="Contraseña" <?php if ($post) { echo 'value="' . $fields['admin']['password'] . '"'; } ?>>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password_repeat" class="col-sm-3 control-label">Repetir contraseña</label>
                                    <div class="col-sm-9">
                                        <input type="password" required="required" class="form-control" id="password_repeat" name="password_repeat" placeholder="Contraseña" <?php if ($post) { echo 'value="' . $fields['admin']['password_repeat'] . '"'; } ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="panel panel-default">
                            <div class="panel-body centered">
                                <button type="submit" name="submit" class="btn btn-primary">Instalar aplicación</button> <button type="reset" name="reset" class="btn btn-default">Limpiar formulario</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.10.1.min.js"><\/script>')</script>

        <script src="js/bootstrap.min.js"></script>
        <script src="js/main.js"></script>
    </body>
</html>