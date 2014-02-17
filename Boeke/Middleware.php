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
     * @param  \Slim\Slim $app     La instancia de la aplicación
     * @param  bool       $reverse Si es verdadero se redirigirá si está conectado.
     * @return \Closure
     */
    public static function isLoggedIn(\Slim\Slim $app, $reverse = false)
    {
        return function () use ($app, $reverse) {
            // Si no está conectado redirigir a la pantalla de entrada
            // Si $reverse es verdadero no lo mandaremos
            if (!(isset($_SESSION['user_id']) &&
                isset($_SESSION['session_hash']))) {
                session_destroy();
                if (!$reverse) {
                    $app->redirect('/login');
                }
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
                    if (!$reverse) {
                        $app->redirect('/login');
                    }
                } elseif ($reverse) {
                    $app->redirect('/');
                }
            }
        };
    }

    /**
     * Middleware para comprobar si el usuario actual es o no administrador.
     * Si el usuario no es administrador mostrará la plantilla de "no
     * autorizado" y parará la ejecución.
     *
     * @param  \Slim\Slim $app       Instancia de la aplicación
     * @param  bool       $checkOnly Solo comprobar en lugar de imprimir plantilla de no autorizado.
     * @return \Closure
     */
    public static function isAdmin(\Slim\Slim $app, $checkOnly = false)
    {
        return function () use ($app) {
            $user = \Model::factory('Usuario')
                ->where('id', $_SESSION['user_id'])
                ->findOne();
            $authorized = false;

            if ($user !== false) {
                $authorized = ($user->es_admin == 1);
            }

            if (!$authorized) {
                if (!$checkOnly) {
                    $app->render('not_authorised.html.twig');
                }
                $app->stop();
            }
        };
    }
}
