<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.4.0
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

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR .
    'Boeke' . DIRECTORY_SEPARATOR . 'config.php';

use Boeke\Middleware;

if ($config['debug']) {
    error_reporting(E_ALL);
}

// Inicializamos la aplicación
$app = new \Slim\Slim(array(
    'mode'                  => ($config['debug']) ? 'development' : 'production',
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

// Configuramos las vistas con Twig
$view = $app->view(new \Slim\Extras\Views\Twig());
$view->parserOptions = array(
    'charset'               => 'utf-8',
    'debug'                 => $config['debug'],
    'cache'                 => realpath('../templates/cache'),
    'auto_reload'           => true,
    'strict_variables'      => false,
    'autoescape'            => true
);
\Slim\Extras\Views\Twig::$twigExtensions = array(
    'Twig_Extensions_Slim',
);

// Configuramos las cookies
$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires'               => $config['cookie_lifetime'],
    'path'                  => $config['cookie_path'],
    'domain'                => null,
    'secure'                => $config['cookie_secure'],
    'httponly'              => $config['cookie_http_only'],
    'name'                  => $config['cookie_name'],
    'secret'                => $config['secret_key'],
    'cipher'                => MCRYPT_RIJNDAEL_256,
    'cipher_mode'           => MCRYPT_MODE_CBC
)));

// Añadimos protección contra ataques CSRF
$app->add(new \Slim\Extras\Middleware\CsrfGuard());

// Hook para añadir todo aquello que las plantillas necesitan globalmente
$app->hook('slim.before', function () use ($app) {
    $posIndex = strpos($_SERVER['PHP_SELF'], '/index.php');
    $baseUrl = substr($_SERVER['PHP_SELF'], 0, $posIndex + 1);
    
    $app->view()->appendData(array(
        'base_url'              => $baseUrl,
        'logged_in'             => isset($_SESSION['session_hash']),
    ));
});

/*
 * Damos al controlador base la referencia a la aplicación y configuración
 * dado que al asignar los manejadores de las rutas con los controladores
 * no pueden asignarse de ninguna otra forma.
 */
\Boeke\Controllers\Base::$app = $app;
\Boeke\Controllers\Base::$config = $config;

/**
 * Rutas
 */

// Índice
$app->get('/', Middleware::isLoggedIn($app), '\\Boeke\\Controllers\\Index::index')
    ->name('index');
   
// Conexión 
$app->map('/login', Middleware::isLoggedIn($app, true), '\\Boeke\\Controllers\\Users::login')
    ->via('GET', 'POST')
    ->name('login');
    
// Desconexión
$app->get('/logout',Middleware::isLoggedIn($app), '\\Boeke\\Controllers\\Users::logout')
    ->name('logout');
    
// Gestión de usuarios
$app->group('/users', Middleware::isLoggedIn($app), function () use ($app) {
    // Listado
    $app->get('/list/(:page)', '\\Boeke\\Controllers\\Users::index')
        ->name('users_index');
    
    // Creación
    $app->map('/new', Middleware::isAdmin($app), '\\Boeke\\Controllers\\Users::create')
        ->via('GET', 'POST')
        ->name('users_new');
    
    // Edición
    $app->map('/edit/:id', Middleware::isAdmin($app), '\\Boeke\\Controllers\\Users::edit')
        ->via('GET', 'PUT')
        ->name('users_edit');
    
    // Borrado
    $app->map('/delete/:id', Middleware::isAdmin($app), '\\Boeke\\Controllers\\Users::delete')
        ->via('GET', 'DELETE')
        ->name('users_delete');
});

// Gestión de niveles
$app->group('/levels', Middleware::isLoggedIn($app), function () use ($app) {
    // Listado
    $app->get('/list/(:page)', '\\Boeke\\Controllers\\Levels::index')
        ->name('levels_index');
    // Listado en formato JSON
    $app->get('/all', '\\Boeke\\Controllers\\Levels::getAll');
    // Creación
    $app->post('/new', '\\Boeke\\Controllers\\Levels::create');
    // Edición
    $app->put('/edit/:id', '\\Boeke\\Controllers\\Levels::edit');
    // Borrado
    $app->delete('/delete/:id', '\\Boeke\\Controllers\\Levels::delete');
});

// Gestión de asignaturas
$app->group('/subjects', Middleware::isLoggedIn($app), function () use ($app) {
    // Listado
    $app->get('/list/(:page)', '\\Boeke\\Controllers\\Subjects::index')
        ->name('subjects_index'); 
    // Listado en formato JSON
    $app->get('/for_level/:level', '\\Boeke\\Controllers\\Subjects::getAll');   
    // Creación
    $app->post('/new', '\\Boeke\\Controllers\\Subjects::create'); 
    // Edición
    $app->put('/edit/:id', '\\Boeke\\Controllers\\Subjects::edit');   
    // Borrado
    $app->delete('/delete/:id', '\\Boeke\\Controllers\\Subjects::delete');
});

$app->run();
