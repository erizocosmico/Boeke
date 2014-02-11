<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.7.0
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
 * Copies
 *
 * Controlador para la gestión de ejemplares.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Copies extends Base
{
    /**
     * @var array Nombre de los estados de un ejemplar.
     */
    private static $statuses = array('Bueno', 'Regular', 'Malo', 'Perdido', 'Baja');
    
    /**
     * Devuelve las opciones de los estados para añadir a un campo select.
     *
     * @return string
     */
    public static function getStatusSelectOptions() {
        $output = '';
        foreach (self::$statuses as $i => $status) {
            $output .= '<option value="' . $i . '">' . $status . '</option>';
        }
        return $output;
    }
    
    /** 
     * Devuelve el nombre para el código de estado seleccionado.
     * 
     * @param int $status
     * @return string
     */
    public static function getStatusName($status) {
        return self::$statuses[$status];
    }

    /**
     * Muestra el listado de ejemplares paginado.
     *
     * @param int $page La página actual, la 1 por defecto
     */
    public static function index($page = 1)
    {
        $app = self::$app;
        $copies = array();
        if ($page < 1) {
            $page = 1;
        }
        
        // Obtenemos los registros
        $copyList = \ORM::forTable('ejemplar')
            ->tableAlias('e')
            ->select('e.*')
            ->select('al.nombre', 'alumno')
            ->select('l.titulo', 'libro')
            ->join('libro', array('e.libro_id', '=', 'l.id'), 'l')
            ->leftOuterJoin('alumno', array('e.alumno_nie', '=', 'al.nie'), 'al')
            ->orderByAsc('e.codigo')
            ->limit('25')
            ->offset((25 * ((int)$page - 1)))
            ->findMany();
        
        foreach ($copyList as $row) {
            if (is_null($row->alumno)) {
                $row->alumno = 'No prestado';
            }
            $row->_estado = self::getStatusName($row->estado);

            $copies[] = $row;
        }
        
        // Generamos la paginación para el conjunto de libros
        $pagination = self::generatePagination(
            \Model::factory('Ejemplar'),
            25,
            $page,
            function ($i) use ($app) {
                return $app->urlFor('copies_index', array('page' => $i));
            }
        );
        
        $app->render('copies_index.html.twig', array(
            'sidebar_copies_active'                 => true,
            'sidebar_copies_list_active'            => true,
            'page'                                  => $page,
            'copies'                                => $copies,
            'status_options'                        => self::getStatusSelectOptions(),
            'pagination'                            => $pagination,
            'breadcrumbs'   => array(
                array(
                    'active'        => true,
                    'text'          => 'Listado de ejemplares',
                    'route'         => self::$app->urlFor('copies_index'),
                ),
            ),
        ));
    }
    
    public static function filter($collection, $type, $id, $page = 1)
    {
        $app = self::$app;
        $skipQuery = false;
        if ($collection !== 'not_returned') {
            $collection = 'all';
        }
        
        switch ($type) {
            case 'level':
                $level = \Model::factory('Nivel')->findOne($id);
                $skipQuery = !$level;
            break;
            
            case 'subject':
                $subject = \Model::factory('Asignatura')->findOne($id);
                $skipQuery = !$subject;
            break;
            
            case 'student':
                $student = \Model::factory('Alumno')->findOne($id);
                $skipQuery = !$student;
            break;
            
            default:
                $type = 'all';
        }
        
        $copies = array();
        
        if (!$skipQuery) {
            $query = \ORM::forTable('ejemplar')
                ->tableAlias('e')
                ->select('e.*')
                ->select('al.nombre', 'alumno')
                ->select('l.titulo', 'libro');
            $params = array();
            
            switch ($type) {
                case 'level':
                    $query = $query
                        ->join('libro', array('e.libro_id', '=', 'l.id'), 'l')
                        ->join('asignatura', array('l.asignatura_id', '=', 'a.id'), 'a')
                        ->join('nivel', array('a.nivel_id', '=', 'n.id'), 'n')
                        ->leftOuterJoin('alumno', array('e.alumno_nie', '=', 'al.nie'), 'al')
                        ->where('n.id', $id);
                break;
                
                case 'subject':
                    $query = $query
                        ->join('libro', array('e.libro_id', '=', 'l.id'), 'l')
                        ->join('asignatura', array('l.asignatura_id', '=', 'a.id'), 'a')
                        ->join('nivel', array('a.nivel_id', '=', 'n.id'), 'n')
                        ->leftOuterJoin('alumno', array('e.alumno_nie', '=', 'al.nie'), 'al')
                        ->where('a.id', $id);
                break;
                
                case 'student':
                    $query = $query
                        ->join('libro', array('e.libro_id', '=', 'l.id'), 'l')
                        ->leftOuterJoin('alumno', array('e.alumno_nie', '=', 'al.nie'), 'al')
                        ->where('e.alumno_nie', $id);
                break;
                
                default:
                    $query = $query
                        ->join('libro', array('e.libro_id', '=', 'l.id'), 'l')
                        ->leftOuterJoin('alumno', array('e.alumno_nie', '=', 'al.nie'), 'al');
            }
            if ($collection === 'not_returned') {
                $query = $query->whereNotNull('e.alumno_nie');
            }
            $copyCount = $query->count();
            $query = $query->orderByAsc('e.codigo')
                ->limit('25')
                ->offset((25 * ((int)$page - 1)));
            $copyList = $query->findMany();
            
            foreach ($copyList as $row) {
                if (is_null($row->alumno)) {
                    $row->alumno = 'No prestado';
                } else {
                    
                }
                $row->_estado = self::getStatusName($row->estado);
                $copies[] = $row;
            }
        } else {
            $copyCount = 0;
        }
        
        // Generamos la paginación para el conjunto de libros
        $pagination = self::generatePagination(
            $copyCount,
            25,
            $page,
            function ($i) use ($app, $collection, $type, $id) {
                return $app->urlFor('copies_filter', array(
                    'collection'    => $collection,
                    'type'          => $type,
                    'id'            => $id,
                    'page'          => $i
                ));
            }
        );
        
        #die(print_r($copies));
        
        $app->render('copies_index.html.twig', array(
            'sidebar_copies_active'                 => true,
            'sidebar_copies_list_active'            => $collection !== 'not_returned',
            'sidebar_copies_not_returned_active'    => $collection === 'not_returned',
            'filter_active'                         => $type !== 'all',
            'page'                                  => $page,
            'copies'                                => $copies,
            'status_options'                        => self::getStatusSelectOptions(),
            'pagination'                            => $pagination,
            'breadcrumbs'   => array(
                // TODO detallar breadcrumbs
                array(
                    'active'        => true,
                    'text'          => 'Listado de ejemplares',
                    'route'         => self::$app->urlFor('copies_index'),
                ),
            ),
        ));
    }
    
    /**
     * Crea los ejemplares recibidos si es accedido mediante POST o muestra el formulario
     * de alta de un lote de ejemplares si es accedido mediante GET.
     */
    public static function create()
    {
        $app = self::$app;
        if ($app->request->isPost()) {
            $codes = array_map(function ($code) {
                return (int)$code;
            }, $app->request->post('codigo'));
            $bookId = (int)$app->request->post('libro');
            $books = $error = array();
            
            $book = \Model::factory('Libro')->findOne($bookId);
            if (!$book) {
                $error[] = 'El libro escogido no existe.';
            }
            
            $copies = \Model::factory('Ejemplar')
                ->whereIn('codigo', $codes)
                ->count();
            
            if ($copies > 0) {
                $error[] = 'Uno o más de los códigos de ejemplares ya existen.';
            }
            
            if (count($error) === 0) {
                $dbh = \ORM::getDb();
                $dbh->beginTransaction();
                try {
                    foreach ($codes as $code) {
                        if ($code > 0) {
                            $bookTmp = \Model::factory('Ejemplar')->create();
                            $bookTmp->codigo = $code;
                            $bookTmp->libro_id = $bookId;
                            $bookTmp->save();
                            
                            Historial::add($code, 'nuevo', $_SESSION['user_id']);
                        }
                    }
                    $dbh->commit();
                    $app->flashNow('success', 'Ejemplares insertados correctamente.');
                } catch (\PDOException $e) {
                    $dbh->rollBack();
                    die($e->getMessage());
                    $error[] = 'Ha ocurrido un error insertando los ejemplares. Ninguno fue insertado para garantizar la integridad de los datos.';
                }
            }
            
            if (count($error) > 0) {
                $app->flashNow('error', join('<br />', $error));
            }
        }
        
        $app->render('copies_create.html.twig', array(
            'sidebar_copies_active'                 => true,
            'sidebar_copies_create_active'            => true,
            'breadcrumbs'   => array(
                array(
                    'active'        => false,
                    'text'          => 'Gestión de ejemplares',
                    'route'         => self::$app->urlFor('copies_index'),
                ),
                array(
                    'active'        => true,
                    'text'          => 'Alta de ejemplares',
                    'route'         => self::$app->urlFor('copies_create'),
                ),
            ),
        ));
    }
    
    /**
     * Edita el libro de un ejemplar.
     *
     * @param int $copyId Código del ejemplar
     */
    public static function edit($copyId)
    {
        $app = self::$app;
        $book = (int)$app->request->put('libro');
        $error = array();
        
        $copy = \Model::factory('Ejemplar')->findOne($copyId);
        if (!$copy) {
            self::jsonResponse(404, array(
                'error'       => 'El ejemplar seleccionado no existe.',
            ));
            return;
        }
        
        $bookTmp = \Model::factory('Libro')->findOne($book);
        if (!$bookTmp) {
            $error[] = 'El libro seleccionado no existe.';
        }
        
        if (count($error) === 0) {
            $copy->libro_id = $book;
            $copy->save();
            
            self::jsonResponse(200, array(
                'message'     => 'Ejemplar editado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }
    
    /**
     * Elimina un ejemplar.
     *
     * @param int $id Código del ejemplar.
     */
    public static function delete($id)
    {
        $app = self::$app;
        
        $copy = \Model::factory('Ejemplar')
            ->findOne($id);

        if (!$copy) {
            self::jsonResponse(404, array(
                'error'       => 'El ejemplar seleccionado no existe.',
            ));
            return;
        }
        
        if ($app->request->delete('confirm') === 'yes') {
            // Borramos el libro
            $copy->delete();
        } else {
            self::jsonResponse(200, array(
                'deleted'     => false,
            ));
            return;
        }
        
        self::jsonResponse(200, array(
            'deleted'     => true,
            'message'     => 'Ejemplar borrado correctamente.',
        ));
    }
    
    /**
     * Actualiza el estado de un ejemplar.
     *
     * @param int $copyId Código del ejemplar
     */
    public static function updateStatus($copyId)
    {
        $app = self::$app;
        $status = (int)$app->request->put('estado');
        $comment = $app->request->put('anotacion', '');
        $error = array();
        
        $copy = \Model::factory('Ejemplar')->findOne($copyId);
        if (!$copy) {
            self::jsonResponse(404, array(
                'error'       => 'El ejemplar seleccionado no existe.',
            ));
            return;
        }
        
        if (count($error) === 0) {
            $dbh = \ORM::getDb();
            $dbh->beginTransaction();
            try {
                $copy->estado = $status;
                $copy->save();
                Historial::add(
                    $copyId,
                    'actualizado',
                    $_SESSION['user_id'],
                    $comment,
                    null,
                    $status
                );
                $dbh->commit();
            } catch (\PDOException $e) {
                self::jsonResponse(400, array(
                    'error'       => 'Se ha producido un error al insertar los datos.',
                ));
                $dbh->rollBack();
                return;
            }
            
            self::jsonResponse(200, array(
                'message'     => 'Estado actualizado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }
}
