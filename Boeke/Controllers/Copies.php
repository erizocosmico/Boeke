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
        $sql = 'SELECT e.*, l.titulo as libro, a.nombre as alumno FROM ejemplar e' . 
            ' JOIN libro l ON (l.id = e.libro_id)' .
            ' LEFT JOIN alumno a ON (e.alumno_nie = a.nie)' .
            ' ORDER BY e.codigo ASC LIMIT ' . (25 * ((int)$page - 1)) . ',25';
        $copyList = \ORM::forTable('ejemplar')->rawQuery($sql)->findMany();
        
        foreach ($copyList as $row) {
            if (is_null($row->alumno)) {
                $row->alumno = 'No prestado';
            }
            $row->estado = self::getStatusName($row->estado);

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
}
