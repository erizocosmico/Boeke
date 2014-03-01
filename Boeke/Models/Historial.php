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
namespace Boeke\Models;

/**
 * Historial
 *
 * Modelo para la tabla historial de la base de datos
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Historial extends \Model
{
    public static $_table = 'historial';
    /**
     * @var array Mapeado de los nombres de los estados a su equivalente numérico.
     */
    private static $types = array(
        'nuevo'         => 0,
        'prestado'      => 1,
        'devuelto'      => 2,
        'actualizado'   => 3,
        'perdido'       => 4,
        'comentario'    => 5,
    );

    /**
     * Añade un registro al historial.
     *
     * @param  int           $copy    Código del ejemplar
     * @param  string        $type    Tipo de registro
     * @param  int|null      $user    Usuario que añade el registro
     * @param  string        $comment Anotación
     * @param  int|null      $nie     Nie del alumno, si procede
     * @param  int           $status  Estado del ejemplar
     * @param  int           $date    UNIX Timestamp del momento en que se añade el registro
     * @throws \PDOException si se produce algún error en la inserción
     */
    public static function add(
        $copy,
        $type,
        $user = null,
        $comment = '',
        $nie = null,
        $status = -1,
        $date = null
    ) {
        if ($status < 0) {
            $copyTmp = \Model::factory('Ejemplar')->findOne($copy);

            if (!$copyTmp) {
                $status = 0;
            } else {
                $status = $copyTmp->estado;
            }
        }

        if ($date === null) {
            $date = time();
        }

        $h = \Model::factory('Historial')->create();
        $h->tipo = self::$types[$type];
        $h->ejemplar_codigo = $copy;
        $h->alumno_nie = $nie;
        $h->usuario_id = $user;
        $h->estado = $status;
        $h->fecha = $date;
        $h->anotacion = $comment;
        $h->save();
    }

    /**
     * Devuelve el alumno asociado al registro del historial
     */
    public function alumno()
    {
        return $this->belongsTo('Alumno');
    }

    /**
     * Devuelve el usuario que insertó el registro en el historial
     */
    public function usuario()
    {
        return $this->belongsTo('Usuario');
    }

    /**
     * Devuelve el ejemplar al que hace referencia el registro del historial
     */
    public function ejemplar()
    {
        return $this->belongsTo('Ejemplar');
    }
}
