<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.12.2
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

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

// Comprobamos si estamos en el instalador
$installing = defined('INSTALLING');

// Alias de DIRECTORY_SEPARATOR para acortar
define('DSEP', DIRECTORY_SEPARATOR);
// Cargamos el autoloader de composer
require dirname(dirname(__FILE__)) . DSEP . 'vendor' . DSEP . 'autoload.php';

// Configuración cargada de config.yml
try {
    $config = Yaml::parse(dirname(dirname(__FILE__)) . DSEP . 'config.yml');
} catch (ParseException $e) {
    // El archivo config.yml no es válido
    $config = false;
}

// Si $config no es un array mostramos un error a menos que estemos en la instalación
if (!is_array($config) && !$installing) {
    die('No se ha encontrado un archivo de configuraci&oacute;n config.yml v&aacute;lido. <a href="install.php">Instalar Boeke</a>.');
}

// Añadimos el prefijo automático para los modelos
Model::$auto_prefix_models = '\\Boeke\\Models\\';
// Configuramos la base de datos
if (!$installing) {
    ORM::configure(array(
        'connection_string' => 'mysql:host=' . $config['database_host'] .
            ';dbname=' . $config['database_name'] .
            ';port=' . $config['database_port'] . ';charset=UTF8',
        'username' => $config['database_user'],
        'password' => $config['database_pass']
    ));
}

try {
    ORM::getDb();
} catch (\PDOException $e) {
    if (!$installing) {
        die('Imposible acceder a la base de datos. Error: ' . $e->getMessage());
    }
}
