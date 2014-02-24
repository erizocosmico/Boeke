<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.12.5
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

use Boeke\Models\Historial;

/**
 * History
 *
 * Controlador encargado de las funciones del historial de ejemplares.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class History extends Base
{
    /**
     * Devuelve un mensaje formateado para el historial dependiendo del tipo de
     * registro que sea.
     *
     * @param  \ORM   $row Fila con los datos del registro del historial
     * @return string
     */
    final private static function historyMessage($row)
    {
        $msg = '';
        switch ($row->tipo) {
            case 0:
                $msg = 'El ejemplar ha sido añadido.';
            break;
            case 1:
            case 2:
            case 4:
                $row->tipo = (int) $row->tipo;
                switch ($row->tipo) {
                    case 2:
                        $action = 'devuelto por el';
                    break;
                    case 4:
                        $action = 'perdido por el';
                    break;
                    default:
                        $action = 'prestado al';
                }
                $msg = 'El ejemplar ha sido ' . $action . ' alumno ';
                $alumno = 'desconocido';
                if ($row->alumno) {
                    $alumno = $row->alumno;

                    if ($row->alumno_apellidos) {
                        $alumno .= ' ' . $row->alumno_apellidos;
                    }
                }
                $msg .= $alumno;
            break;
            case 3:
                $msg = 'El estado del ejemplar ha sido actualizado.';
            break;
            case 5:
                $msg = 'Se ha añadido una anotación.';
            break;
        }

        return $msg;
    }

    /**
     * Muestra el historial de un ejemplar.
     *
     * @param int $code Código del ejemplar.
     */
    public static function view($code)
    {
        $app = self::$app;

        $history = array();
        $copy = \ORM::forTable('ejemplar')
            ->tableAlias('e')
            ->select('e.*')
            ->select('l.*')
            ->select('a.nombre', 'asignatura')
            ->select('al.nombre', 'alumno')
            ->select('al.apellidos', 'alumno_apellidos')
            ->select('n.nombre', 'nivel')
            ->join('libro', array('l.id', '=', 'e.libro_id'), 'l')
            ->join('asignatura', array('a.id', '=', 'l.asignatura_id'), 'a')
            ->leftOuterJoin('alumno', array('al.nie', '=', 'e.alumno_nie'), 'al')
            ->join('nivel', array('n.id', '=', 'a.nivel_id'), 'n')
            ->where('e.codigo', $code)
            ->findOne();

        if (!$copy) {
            $copy = null;
            $app->flashNow('error', 'No pudo encontrarse el ejemplar.');
        } else {
            if (!$copy->alumno) {
                $copy->alumno = 'No prestado';
            } else {
                if ($copy->alumno_apellidos) {
                    $copy->alumno .= ' ' . $copy->alumno_apellidos;
                }
            }
            $copy->estado = Copies::$statuses[$copy->estado];

            $historyList = \ORM::forTable('historial')
                ->tableAlias('h')
                ->select('h.*')
                ->select('al.nombre', 'alumno')
                ->select('al.apellidos', 'alumno_apellidos')
                ->select('u.nombre_usuario', 'usuario')
                ->leftOuterJoin('alumno', array('al.nie', '=', 'h.alumno_nie'), 'al')
                ->leftOuterJoin('usuario', array('u.id', '=', 'h.usuario_id'), 'u')
                ->where('ejemplar_codigo', $code)
                ->orderByAsc('h.fecha')
                ->findMany();

            foreach ($historyList as $row) {
                $date = new \DateTime();
                $date->setTimestamp($row->fecha);
                $history[] = array(
                    'message'       => self::historyMessage($row),
                    'date'          => $date->format('h:m, d/m/Y'),
                    'user'          => ($row->usuario) ? $row->usuario : 'Desconocido',
                    'status'        => Copies::$statuses[$row->estado],
                    'comment'       => $row->anotacion,
                );
            }
        }

        $app->render('history_view.html.twig', array(
            'sidebar_copies_active'                 => true,
            'sidebar_copies_list_active'            => true,
            'code'                                  => $code,
            'copy'                                  => $copy,
            'history'                               => $history,
            'breadcrumbs'                           => array(
                array(
                    'active'        => false,
                    'text'          => 'Gestión de ejemplares',
                    'route'         => self::$app->urlFor('copies_index'),
                ),
                array(
                    'active'        => false,
                    'text'          => 'Listado de ejemplares',
                    'route'         => self::$app->urlFor('copies_index'),
                ),
                array(
                    'active'        => true,
                    'text'          => 'Historial de un ejemplar',
                    'route'         => '',
                ),
            ),
        ));
    }

    /**
     * Añade una anotación al historial de un ejemplar.
     *
     * @param int $code Código del ejemplar
     */
    public static function comment($code)
    {
        $app = self::$app;
        $comment = $app->request->post('comment', '');

        $copy = \Model::factory('Ejemplar')->findOne($code);

        if (!empty($comment) && $copy) {
            try {
                Historial::add(
                    $code,
                    'comentario',
                    $_SESSION['user_id'],
                    $comment
                );

                $app->flash('success', 'Anotación añadida correctamente.');
            } catch (\PDOException $e) {
                $app->flash('error', 'Error insertando la anotación.');
            }
        }

        $app->redirect($app->urlFor('history_view', array('code' => $code)));
    }
}
