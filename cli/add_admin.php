<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.5.2
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

require dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR .
    'public' . DIRECTORY_SEPARATOR . 'config.php';

if (count($argv) < 4) {
    die('Uso: add_admin.php <nombre de usuario> <contraseña> <nombre completo>');
}

try {
    $user = Model::factory('Usuario')->create();
    $user->nombre_usuario = $argv[1];
    $user->usuario_pass = sha1($config['password_salt'] . $argv[2]);
    $user->nombre_completo = $argv[3];
    $user->es_admin = 1;
    $user->save();
} catch (\Exception $e) {
    die('Error al crear el usuario: ' . $e->getMessage() . '\n');
}

echo 'Usuario creado correctamente.\n';