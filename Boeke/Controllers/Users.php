<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     1.0.0
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

/**
 * Users
 *
 * Controlador para la gestión de usuarios.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Users extends Base
{
    /**
     * Si es accedida mediante GET muestra el formulario de conexión
     * si es accedida mediante POST procesa dicho formulario.
     */
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
            // Buscamos al usuario
            $user = \Model::factory('Usuario')
                ->where('nombre_usuario', $request->post('username'))
                ->findOne();

            $valid = false;
            if ($user) {
                // Comprobamos si la contraseña coincide
                if (password_verify($request->post('password'), $user->usuario_pass)) {
                    $valid = true;
                }
            }

            if (!$valid) {
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
            'breadcrumbs'   => array(
                array(
                    'active'        => true,
                    'text'          => 'Entrar',
                    'route'         => self::$app->urlFor('login'),
                ),
            ),
        ));
    }

    /**
     * Cierra la sesión del usuario y lo redirige al login
     */
    public static function logout()
    {
        self::$app->deleteCookie(self::$config['cookie_name']);

        // Borramos la sesión
        \Model::factory('Sesion')
            ->where('hash_sesion', $_SESSION['session_hash'])
            ->findOne()
            ->delete();

        // Nos deshacemos de todas las variables de sesión
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
        // Si redirigimos al índice y no está conectado lo redirigirá
        // al login, así que nos ahorramos una redirección y
        // lo mandamos directamente al login.
        self::$app->redirect('/login');
    }

    /**
     * Muestra el listado de usuarios paginado.
     *
     * @param int $page La página actual, la 1 por defecto
     */
    public static function index($page = 1)
    {
        $app = self::$app;
        $users = array();

        // Obtenemos los registros
        $userList = \Model::factory('Usuario')
            ->limit(25)
            ->offset(25 * ((int) $page - 1))
            ->orderByAsc('id')
            ->findArray();

        foreach ($userList as $row) {
            $users[] = $row;
        }

        // Generamos la paginación para el conjunto de usuarios
        $pagination = self::generatePagination(
            \Model::factory('Usuario'),
            25,
            $page,
            function ($i) use ($app) {
                return $app->urlFor('users_index', array('page' => $i));
            }
        );

        try {
            $isAdminCallback = \Boeke\Middleware::isAdmin($app, true);
            $isAdminCallback();
            $isAdmin = true;
        } catch (\Exception $e) {
            $isAdmin = false;
        }

        $app->render('users_index.html.twig', array(
            'sidebar_users_active'                  => true,
            'is_admin'                              => $isAdmin,
            'page'                                  => $page,
            'users'                                 => $users,
            'pagination'                            => $pagination,
            'breadcrumbs'   => array(
                array(
                    'active'        => true,
                    'text'          => 'Listado de usuarios',
                    'route'         => self::$app->urlFor('users_index'),
                ),
            ),
        ));
    }

    /**
     * Si es accedida mediante GET muestra el formulario de creación de usuario
     * si es accedida mediante POST procesa la creación del usuario.
     */
    public static function create()
    {
        $app = self::$app;

        $error = array();
        $username = $app->request->post('nombre_usuario');
        $fullName = $app->request->post('nombre_completo');
        $password = $app->request->post('usuario_pass');
        $isAdmin = (int) $app->request->post('es_admin', 0);

        // Validamos los posibles errores
        if (empty($username)) {
            $error[] = 'El nombre de usuario es obligatorio.';
        } else {
            $user = \Model::factory('Usuario')
                ->where('nombre_usuario', $username)
                ->findOne();

            if ($user) {
                $error[] = 'El nombre de usuario ya está en uso.';
            }
        }

        if (strlen($password) < 6) {
            $error[] = 'La contraseña debe tener un mínimo de 6 caracteres.';
        }

        // Si no hay errores lo creamos
        if (count($error) == 0) {
            $user = \Model::factory('Usuario')->create();
            $user->nombre_usuario = $username;
            $user->nombre_completo = $fullName;
            $user->usuario_pass = password_hash($password, PASSWORD_BCRYPT);
            $user->es_admin = $isAdmin;
            $user->save();

            self::jsonResponse(201, array(
                'message'       => 'Usuario creado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }

    /**
     * Si es accedida mediante GET muestra el formulario de edición de usuario
     * si es accedida mediante PUT procesa la edición del usuario.
     *
     * @param int $userId La id del usuario a editar
     */
    public static function edit($userId)
    {
        $app = self::$app;

        $user = \Model::factory('Usuario')
            ->where('id', $userId)
            ->findOne();

        if (!$user) {
            self::jsonResponse(404, array(
                'error'       => 'El usuario seleccionado no existe.',
            ));

            return;
        }

        $error = array();

        $username = $app->request->put('nombre_usuario');
        $fullName = $app->request->put('nombre_completo');
        $password = $app->request->put('usuario_pass');
        $isAdmin = (int) $app->request->put('es_admin', 0);

        // Validamos los campos
        if (empty($username)) {
            $error[] = 'El nombre de usuario es obligatorio.';
        } else {
            $userTmp = \Model::factory('Usuario')
                ->where('nombre_usuario', $username)
                ->whereNotEqual('id', $userId)
                ->findOne();

            if ($userTmp) {
                $error[] = 'El nombre de usuario ya está en uso.';
            }
        }

        if (strlen($password) < 6 && !empty($password)) {
            $error[] = 'La contraseña debe tener un mínimo de 6 caracteres.';
        }

        // Si no hay errores editamos el usuario
        if (count($error) == 0) {
            $user->nombre_usuario = $username;
            $user->nombre_completo = $fullName;
            if (!empty($password)) {
                $user->usuario_pass = password_hash($password, PASSWORD_BCRYPT);
            }
            $user->es_admin = $isAdmin;
            $user->save();

            self::jsonResponse(200, array(
                'message'       => 'Usuario editado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }

    /**
     * Si es accedida mediante GET muestra el formulario de borrado de usuario
     * si es accedida mediante DELETE procesa el borrado del usuario.
     *
     * @param int $userId La id del usuario a borrar
     */
    public static function delete($userId)
    {
        $app = self::$app;

        $user = \Model::factory('Usuario')
            ->where('id', $userId)
            ->findOne();

        if (!$user) {
            self::jsonResponse(404, array(
                'error'       => 'El usuario seleccionado no existe.',
            ));

            return;
        }

        if ($app->request->delete('confirm') === 'yes') {
            // Borramos el usuario
            \Model::factory('Usuario')
                ->findOne($userId)
                ->delete();
        } else {
            self::jsonResponse(200, array(
                'deleted'     => false,
            ));

            return;
        }

        self::jsonResponse(200, array(
            'deleted'     => true,
            'message'     => 'Usuario borrado correctamente.',
        ));
    }
}
