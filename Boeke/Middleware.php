<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.0.1
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
namespace Boeke;

/**
 * Middleware
 *
 * Clase que proporciona middlewares necesarios para la aplicación.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Middleware
{
    /**
     * Middleware para comprobar si el usuario está o no conectado.
     *
     * @param \Slim\Slim $app La instancia de la aplicación
     * @return \Closure
     */
    public function isLoggedIn(\Slim\Slim $app)
    {
        return function () use ($app) {
            // Si no está conectado redirigir a la pantalla de entrada
            if (!(isset($_SESSION['user_id']) &&
                isset($_SESSION['session_hash']))) {
                session_destroy();
                $app->redirect('/login');
            } else {
                // Si está conectado debemos determinar si la id del usuario
                // se corresponde con el hash de la sesión.
                $session = \Model::factory('Sesion')
                    ->where('hash_sesion', $_SESSION['session_hash'])
                    ->where('usuario_id', $_SESSION['user_id'])
                    ->findOne();
                
                // Si no se corresponde enviarlo a la entrada de conexión
                if ($session === false) {
                    unset($_SESSION['user_id']);
                    unset($_SESSION['session_hash']);
                    $app->redirect('/login');
                }
            }
        };
    }
}