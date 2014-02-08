<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.6.3
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

set_include_path(dirname(__FILE__) . '/../' . PATH_SEPARATOR . get_include_path());

require_once 'vendor/autoload.php';

function customAutoLoader($class)
{
    $file = rtrim(dirname(__FILE__), '/') . '/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    } else {
        return;
    }
}

spl_autoload_register('customAutoLoader');

// Configuración cargada de config.yml
$config = Yaml::parse('config.yml');

if ($config['debug']) {
    error_reporting(E_ALL);
}

// Inicializamos la aplicación
$app = new \Slim\Slim(array(
    'mode'                  => ($config['debug']) ? 'development' : 'production',
    'view'                  => new \Slim\Extras\Views\Twig(),
    'debug'                 => $config['debug'],
    'templates.path'        => dirname(dirname(__FILE__)) . DSEP . 'templates',
    'log.level'             => ($config['debug']) ? \Slim\Log::DEBUG : \Slim\Log::WARN,
    'log.enabled'           => true,
    'cookies.encrypt'       => true,
    'cookies.lifetime'      => $config['cookie_lifetime'],
    'cookies.path'          => $config['cookie_path'],
    'cookies.secret_key'    => $config['secret_key'],
    'http.version'          => '1.1',
));

// Inicializamos la base de datos
$driver = new \Aurora\Drivers\MySQLDriver(
    $config['database_host'],
    $config['database_name'],
    $config['database_port'],
    $config['database_user'],
    $config['database_pass']
);
\Aurora\Dbal::init($driver);