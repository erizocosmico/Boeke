<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.2.0
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

define('INSTALLING', true);

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';

if (@$config['debug']) {
    error_reporting(E_ALL);
}

function requestVar($name, $default = '')
{
    return (isset($_POST[$name])) ? $_POST[$name] : $default;
}

$messages = array(
    'database'  => array(),
    'cookie'    => array(),
    'general'   => array(),
    'admin'     => array(),
);

$errors = array(
    'database_host'             => 'El servidor de la base de datos es obligatorio.',
    'database_user'             => 'El usuario de la base de datos es obligatorio.',
    'database_port'             => 'El puerto de la base de datos es obligatorio.',
    'database_pass'             => 'La contraseña del usuario de la base de datos es obligatorio.',
    'database_name'             => 'El nombre de la base de datos es obligatorio.',
    
    'cookie_path'               => 'Debes especificar una ruta para la cookie. Por defecto es /.',
    'cookie_lifetime'           => 'Debes especificar una duración para la cookie del tipo: 15 seconds, 25 days, 40 hours, ...',
    'cookie_name'               => 'Debes especificar un nombre para la cookie.',
    
    'general_secret_key'        => 'La clave secreta debe tener entre 8 y 60 caracteres.',
    'general_password_salt'     => 'El salto de la contraseña debe tener entre 8 y 60 caracteres.',
    'general_max_retries'       => 'El número máximo de intentos de conexión debe ser mayor a 0.',
    'general_login_block'       => 'La duración del bloqueo de conexión debe ser mayor a 0.',
    
    'admin_username'            => 'El nombre de usuario debe tener entre 5 y 60 caracteres.',
    'admin_full_name'           => 'El nombre completo debe tener entre 3 y 90 caracteres.',
    'admin_password'            => 'La contraseña debe tener entre 5 y 60 caracteres.',
);

$post = false;
$success = false;

// ¿Existe el archivo de configuración?
if (file_exists(dirname(dirname(__FILE__)) . DSEP . 'config.yml')) {
    $messages['database'][] = 'Ya existe un archivo de configuración <strong>config.yml</strong>. Se abortará la instalación.';
} else {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $post = true;
        
        $fields = array(
            'database'      => array(
                'host'          => requestVar('db_server'),
                'user'          => requestVar('db_user'),
                'port'          => (int)requestVar('db_port'),
                'pass'          => requestVar('db_pass'),
                'name'          => requestVar('db_name'),
            ),
            'cookie'        => array(
                'path'          => requestVar('cookie_path', '/'),
                'secure'        => (bool)requestVar('cookie_secure', false),
                'http_only'     => (bool)requestVar('cookie_http_only', false),
                'lifetime'      => requestVar('cookie_lifetime'),
                'name'          => requestVar('cookie_name'),
            ),
            'general'       => array(
                'secret_key'            => requestVar('secret_key'),
                'password_salt'         => requestVar('pw_salt'),
                'login_max_retries'     => (int)requestVar('login_max_retries'),
                'login_block'           => (int)requestVar('login_block'),
            ),
            'admin'         => array(
                'username'      => requestVar('nombre_usuario'),
                'full_name'     => requestVar('nombre_completo'),
                'password'      => requestVar('usuario_pass'),
            ),
        );
            
        foreach ($fields as $type => $content) {
            foreach ($content as $key => $value) {
                $fieldName = $type . '_' . $key;
                if (is_int($value)) {
                    if ($value < 1) {
                        $messages[$type][] = $errors[$fieldName];
                    }
                } elseif (is_string($value)) {
                    $error = false;
                    if ($key == 'secret_key' || $key == 'password_salt') {
                        if (strlen($value) < 8 || strlen($value) > 60) {
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
                    } elseif ($key == 'lifetime') {
                        $parts = explode(' ', $value);
                        if (count($parts) != 2) {
                            $error = true;
                        } else {
                            list($time, $unit) = $parts;
                            
                            if ((int)$time === 0) {
                                $error = true;
                            } elseif (!in_array($unit, array(
                                'second',
                                'minute',
                                'hour',
                                'day',
                                'week',
                                'month',
                                'seconds',
                                'minutes',
                                'hours',
                                'days',
                                'weeks',
                                'months'
                            ))) {
                                $error = true;
                            }
                        }
                    }
                    
                    if ($error) {
                        $messages[$type][] = $errors[$fieldName];
                    }
                }
            }
        }
        
        if (count(array_filter($messages, function ($item) {
            return count($item) > 0;
        })) === 0) {
            $connectionWorks = true;
                
            try{
                $dbh = new \PDO(
                    'mysql:host=' .
                    $fields['database']['host'] .
                    ';dbname=' . $fields['database']['name'] .
                    ';port=' . $fields['database']['port'],
                    $fields['database']['user'],
                    $fields['database']['pass'],
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );
            }
            catch(\PDOException $e){
                $connectionWorks = false;
                $messages['database'][] = 'Los datos proporcionados no son correctos. No puede establecerse conexión.';
            }
            
            if ($connectionWorks) {
                try {
                    $stmt = $dbh->prepare('SELECT * FROM usuarix LIMIT 1');
                    $stmt->execute();
                    
                    $messages['database'][] = 'Hay una instalación previa en la base de datos. Se abortará la instalación.';
                } catch (\PDOException $e) {
                    $continue = true;
                    // Cargar las tablas de la base de datos
                    $dbh->beginTransaction();
                    try {
                        
                        $dbh->exec("CREATE TABLE IF NOT EXISTS `usuario` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `nombre_usuario` VARCHAR(60) NOT NULL,
                        `nombre_completo` VARCHAR(90) NOT NULL,
                        `usuario_pass` VARCHAR(40) NOT NULL,
                        `es_admin` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                        PRIMARY KEY (`id`),
                        UNIQUE INDEX `nombre_usuario_UNIQUE` (`nombre_usuario` ASC))
                        ENGINE = InnoDB;");
                        
                        $dbh->exec("CREATE TABLE IF NOT EXISTS `sesion` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `hash_sesion` VARCHAR(255) NOT NULL,
                        `usuario_id` INT UNSIGNED NOT NULL,
                        `creada` BIGINT UNSIGNED NOT NULL,
                        `ultima_visita` BIGINT UNSIGNED NOT NULL,
                        PRIMARY KEY (`id`),
                        INDEX `usuario_id_fk_idx` (`usuario_id` ASC),
                        CONSTRAINT `sesion_usuario_id_fk`
                        FOREIGN KEY (`usuario_id`)
                        REFERENCES `usuario` (`id`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE)
                        ENGINE = InnoDB;");
                        
                        $dbh->exec("CREATE TABLE IF NOT EXISTS `nivel` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `nombre` VARCHAR(40) NOT NULL,
                        PRIMARY KEY (`id`))
                        ENGINE = InnoDB;");
                        
                        $dbh->exec("CREATE TABLE IF NOT EXISTS `asignatura` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `nivel_id` INT UNSIGNED NOT NULL,
                        `nombre` VARCHAR(60) NOT NULL,
                        PRIMARY KEY (`id`, `nombre`),
                        INDEX `nivel_id_fk_idx` (`nivel_id` ASC),
                        CONSTRAINT `asignatura_nivel_id_fk`
                        FOREIGN KEY (`nivel_id`)
                        REFERENCES `nivel` (`id`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE)
                        ENGINE = InnoDB;");
                        
                        $dbh->exec("CREATE TABLE IF NOT EXISTS `alumno` (
                        `nie` BIGINT UNSIGNED NOT NULL,
                        `nombre` VARCHAR(70) NOT NULL,
                        `apellidos` VARCHAR(70) NOT NULL DEFAULT '',
                        `telefono` VARCHAR(9) NOT NULL DEFAULT '',
                        PRIMARY KEY (`nie`))
                        ENGINE = InnoDB;");
                        
                        $dbh->exec("CREATE TABLE IF NOT EXISTS `libro` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `isbn` VARCHAR(13) NOT NULL,
                        `titulo` VARCHAR(80) NOT NULL,
                        `autor` VARCHAR(85) NOT NULL,
                        `anio` INT UNSIGNED NOT NULL,
                        `asignatura_id` INT UNSIGNED NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE INDEX `isbn_UNIQUE` (`isbn` ASC),
                        INDEX `asignatura_id_fk_idx` (`asignatura_id` ASC),
                        CONSTRAINT `libro_asignatura_id_fk`
                        FOREIGN KEY (`asignatura_id`)
                        REFERENCES `asignatura` (`id`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE)
                        ENGINE = InnoDB;");
                        
                        $dbh->exec("CREATE TABLE IF NOT EXISTS `ejemplar` (
                        `codigo` INT UNSIGNED NOT NULL,
                        `libro_id` INT UNSIGNED NOT NULL,
                        `estado` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                        `alumno_nie` BIGINT UNSIGNED NULL,
                        PRIMARY KEY (`codigo`),
                        INDEX `ejemplar_alumno_nie_fk_idx` (`alumno_nie` ASC),
                        CONSTRAINT `ejemplar_alumno_nie_fk`
                        FOREIGN KEY (`alumno_nie`)
                        REFERENCES `alumno` (`nie`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE,
                        INDEX `ejemplar_libro_id_fk_idx` (`libro_id` ASC),
                        CONSTRAINT `ejemplar_libro_id_fk`
                        FOREIGN KEY (`libro_id`)
                        REFERENCES `libro` (`id`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE)
                        ENGINE = InnoDB;");
                        
                        $dbh->exec("CREATE TABLE IF NOT EXISTS `historial` (
                        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        `tipo` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                        `ejemplar_codigo` INT UNSIGNED NOT NULL,
                        `alumno_nie` BIGINT UNSIGNED NULL,
                        `estado` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                        `fecha` BIGINT UNSIGNED NOT NULL,
                        `anotacion` BLOB NOT NULL,
                        `usuario_id` INT UNSIGNED NOT NULL,
                        PRIMARY KEY (`id`),
                        INDEX `historial_ejemplar_codigo_fk_idx` (`ejemplar_codigo` ASC),
                        INDEX `historial_alumno_nie_fk_idx` (`alumno_nie` ASC),
                        CONSTRAINT `historial_ejemplar_codigo_fk`
                        FOREIGN KEY (`ejemplar_codigo`)
                        REFERENCES `ejemplar` (`codigo`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE,
                        CONSTRAINT `historial_alumno_nie_fk`
                        FOREIGN KEY (`alumno_nie`)
                        REFERENCES `alumno` (`nie`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE,
                        CONSTRAINT `historial_usuario_id_fk`
                        FOREIGN KEY (`usuario_id`)
                        REFERENCES `usuario` (`id`)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE)
                        ENGINE = InnoDB;");
                    } catch (\PDOException $e) {
                        $messages['database'][] = 'Error creando las tablas de la base de datos.' . $e->getMessage();
                        $continue = false;
                        $dbh->rollBack();
                    }
                    
                    // Crear usuario administrador
                    if ($continue) {
                        try {
                            $stmt = $dbh->prepare('INSERT INTO usuario (nombre_usuario, nombre_completo, usuario_pass, es_admin) VALUES (?, ?, ?, 1)');
                            $stmt->execute(array(
                                $fields['admin']['username'],
                                $fields['admin']['full_name'],
                                sha1($fields['general']['password_salt'] .
                                $fields['admin']['password']),
                            ));
                            
                            $dbh->commit();
                            $success = true;
                        } catch (\PDOException $e) {
                            $messages['database'][] = 'Error al crear el usuario administrador.';
                            $continue = false;
                            $dbh->rollBack();
                        }
                    }
                    
                    // Generar el yml de configuración
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
                    
                        if (!is_writable(dirname(dirname(__FILE__)))) {
                            file_put_contents(
                                dirname(dirname(__FILE__)) . DSEP . 'config.yml',
                                $ymlOutput
                            );
                        } else {
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

        <link rel="stylesheet" href="css/bootstrap.min.css">
        <link rel="stylesheet" href="css/font-awesome.min.css">
        <link rel="stylesheet" href="css/main.css">

        <script src="js/vendor/modernizr-2.6.2.min.js"></script>
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
                                <h4>Configuración de las cookies</h4>
                            </div>
                            <div class="panel-body">
                                <?php if (count($messages['cookie']) > 0): ?>
                                    <div class="alert alert-danger">
                                        <?= join('<br />', $messages['cookie']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label for="cookie_path" class="col-sm-3 control-label">Ruta de la cookie</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="cookie_path" name="cookie_path" placeholder="Ruta de la cookie, / por defecto" <?php if ($post) { echo 'value="' . $fields['cookie']['path'] . '"'; } ?>>
                                    </div>
                                </div>
                        
                                <div class="form-group">
                                    <label for="cookie_name" class="col-sm-3 control-label">Nombre de la cookie</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="cookie_name" name="cookie_name" placeholder="Nombre de la cookie" <?php if ($post) { echo 'value="' . $fields['cookie']['name'] . '"'; } ?>>
                                    </div>
                                </div>
                        
                                <div class="form-group">
                                    <label for="cookie_lifetime" class="col-sm-3 control-label">Duración de la cookie</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="cookie_lifetime" name="cookie_lifetime" placeholder="Duración de la cookie" <?php if ($post) { echo 'value="' . $fields['cookie']['lifetime'] . '"'; } ?>>
                                    </div>
                                    <div class="col-sm-9 col-sm-offset-3">
                                        <small><strong>Ejemplo:</strong> 10 minutes, 15 hours, 20 days, ...</small>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <label>
                                            <input type="checkbox" name="cookie_secure" value="1" <?php if ($post) { if ($fields['cookie']['secure']) { echo 'checked'; } } ?>> Usar cookie segura (HTTPS)
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="col-sm-offset-3 col-sm-9">
                                        <label>
                                            <input type="checkbox" name="cookie_http_only" value="1" <?php if ($post) { if ($fields['cookie']['http_only']) { echo 'checked'; } } ?>> Permitir cookies solo por HTTP
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Configuración general</h4>
                            </div>
                            <div class="panel-body">
                                <?php if (count($messages['general']) > 0): ?>
                                    <div class="alert alert-danger">
                                        <?= join('<br />', $messages['general']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label for="secret_key" class="col-sm-3 control-label">Clave secreta</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="secret_key" name="secret_key" placeholder="Clave secreta para el cifrado" <?php if ($post) { echo 'value="' . $fields['general']['secret_key'] . '"'; } ?>>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="pw_salt" class="col-sm-3 control-label">Salto de contraseña</label>
                                    <div class="col-sm-9">
                                        <input type="text" required="required" class="form-control" id="pw_salt" name="pw_salt" placeholder="Salto para las contraseñas" <?php if ($post) { echo 'value="' . $fields['general']['password_salt'] . '"'; } ?>>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="login_max_retries" class="col-sm-3 control-label">Máximos intentos de conexión</label>
                                    <div class="col-sm-9">
                                        <input type="number" required="required" class="form-control" id="login_max_retries" name="login_max_retries" placeholder="Número máximo de intentos de conexión" <?php if ($post) { echo 'value="' . $fields['general']['login_max_retries'] . '"'; } ?>>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="login_block" class="col-sm-3 control-label">Bloqueo de conexión</label>
                                    <div class="col-sm-9">
                                        <input type="number" required="required" class="form-control" id="login_block" name="login_block" placeholder="Bloqueo en segundos tras los intentos máximos de conexión" <?php if ($post) { echo 'value="' . $fields['general']['login_block'] . '"'; } ?>>
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