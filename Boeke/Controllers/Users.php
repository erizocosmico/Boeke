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
namespace Boeke\Controllers;

class Users extends Base
{
    public static function login()
    {
        // ¿Debemos procesar la petición en caso de ser post?
        $processRequest = true;
        
        // Comprobamos si el acceso le fue bloqueado al usuario
        // si lo fue ignoraremos la petición si es post y mostraremos
        // um mensaje. Si lo fue lo dejaremos entrar.
        if (isset($_SESSION['block_started'])) {
            if ($_SESSION['block_started'] > time()) {
                self::$app->flashNow('error', 'Tienes el acceso bloqueado. Intenta de nuevo en unos minutos.');
                $processRequest = false;
            } else {
                unset($_SESSION['block_started']);
            }
        }
        
        $request = self::$app->request;
        if ($request->isPost() && $processRequest) {
            // Preparamos la contraseña cifrada
            $password = sha1(self::$config['password_salt'] .
                $request->post('password'));
            
            // Buscamos al usuario
            $user = \Model::factory('Usuario')
                ->where('nombre_usuario', $request->post('username'))
                ->where('usuario_pass', $password)
                ->findOne();
            
            if ($user === false) {
                self::$app->flashNow('error', 'Nombre o contraseña incorrectos.');
                
                // Miramos los intentos que le quedan al usuario
                if (isset($_SESSION['retries_left'])) {
                    $_SESSION['retries_left']--;
                } else {
                    $_SESSION['retries_left'] = self::$config['login_max_retries'];
                }
                
                // Si ha agotado sus intentos le bloqueamos el acceso
                if ($_SESSION['retries_left'] == 0) {
                    $_SESSION['block_started'] = time() +
                        self::$config['login_block'];
                }
            } else {
                // Si ha acertado le quitamos los máximos intentos
                if (isset($_SESSION['retries_left'])) {
                    unset($_SESSION['retries_left']);
                }
                
                // Creamos la sesión
                $_SESSION['user_id'] = $user->id;
                $_SESSION['session_hash'] = sha1(uniqid());
                $session = \Model::factory('Sesion')->create();
                $session->usuario_id = $user->id;
                $session->creada = time();
                $session->ultima_visita = time();
                $session->hash_sesion = $_SESSION['session_hash'];
                $session->save();
                
                // Redirigimos al índice
                self::$app->redirect('/');
            }
        }
        
        self::$app->render('login.html.twig', array(
            'action'        => self::$app->urlFor('login'),
        ));
    }
}