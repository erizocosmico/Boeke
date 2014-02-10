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
 * Subjects
 *
 * Controlador para la gestión de asignaturas.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Subjects extends Base
{  
    /**
     * Muestra el listado de asignaturas paginado.
     *
     * @param int $page La página actual, la 1 por defecto
     */
    public static function index($page = 1)
    {
        $app = self::$app;
        $subjects = array();
        
        // Obtenemos los registros
        $subjectList = \ORM::forTable('asignatura')
            ->tableAlias('a')
            ->select('a.*')
            ->select('n.nombre', 'nivel')
            ->join('nivel', array('a.nivel_id', '=', 'n.id'), 'n')
            ->limit(25)
            ->offset(25 * ((int)$page - 1))
            ->orderByAsc('a.id')
            ->findMany();
        
        foreach ($subjectList as $row) {
            $subjects[] = array(
                'id'            => $row->id,
                'nombre'        => $row->nombre,
                'nivel_id'      => $row->nivel_id,
                'nivel'         => $row->nivel,
            );
        }
        
        // Generamos la paginación para el conjunto de asignaturas
        $pagination = self::generatePagination(
            \Model::factory('Asignatura'),
            25,
            $page,
            function ($i) use ($app) {
                return $app->urlFor('subjects_index', array('page' => $i));
            }
        );
        
        $app->render('subjects_index.html.twig', array(
            'sidebar_subjects_active'                  => true,
            'page'                                  => $page,
            'subjects'                                 => $subjects,
            'pagination'                            => $pagination,
            'breadcrumbs'   => array(
                array(
                    'active'        => true,
                    'text'          => 'Listado de asignaturas',
                    'route'         => self::$app->urlFor('subjects_index'),
                ),
            ),
        ));
    }
    
    /**
     * Devuelve en formato JSON todas las asignaturas.
     */
    public static function getAll()
    {
        $app = self::$app;
        $subjectList = \ORM::forTable('asignatura')
            ->tableAlias('a')
            ->select('a.*')
            ->select('n.nombre', 'nivel')
            ->join('nivel', array('a.nivel_id', '=', 'n.id'), 'n')
            ->findMany();
        
        $subjects = array();
        foreach ($subjectList as $row) {
            $subjects[] = array(
                'id'            => $row->id,
                'name'          => $row->nombre . ' (' . $row->nivel . ')',
            );
        }
        
        self::jsonResponse(200, array(
            'subjects'       => $subjects,
        ));
    }
    
    /**
     * Devuelve en formato JSON todas las asignaturas para un nivel.
     * 
     * @param int $level Nivel para el cual se devuelven las asignaturas.
     */
    public static function forLevel($level)
    {
        $app = self::$app;
        $subjects = array_map(
            function ($subject) {
                return array(
                    'id'    => $subject['id'],
                    'name'  => $subject['nombre'],
                );
            },
            \Model::factory('Asignatura')
                ->where('nivel_id', (int)$level)
                ->findArray()
        );
        
        self::jsonResponse(200, array(
            'subjects'       => $subjects,
        ));
    }
    
    /**
     * Se encarga de crear una asignatura.
     */
    public static function create()
    {
        $app = self::$app;
        $error = array();
        $subjectName = $app->request->post('nombre');
        $levelId = (int)$app->request->post('nivel');
        
        $level = \Model::factory('Nivel')
            ->where('id', $levelId)
            ->findOne();

        // Validamos los posibles errores
        if (empty($subjectName)) {
            $error[] = 'El nombre de asignatura es obligatorio.';
        } else {
            $subject = \Model::factory('Asignatura')
                ->where('nivel_id', $levelId)
                ->where('nombre', $subjectName)
                ->findOne();
            
            if ($subject) {
                $error[] = 'El nombre de asignatura ya está en uso.';
            }
        }
        
        if (!$level) {
            $error[] = 'El nivel seleccionado no existe o no es válido.';
        }
        
        // Si no hay errores lo creamos
        if (count($error) == 0) {
            $subject = \Model::factory('Asignatura')->create();
            $subject->nombre = $subjectName;
            $subject->nivel_id = $levelId;
            $subject->save();
            
            self::jsonResponse(201, array(
                'message'       => 'Asignatura creada correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }
    
    /**
     * Se encarga de editar una asignatura.
     *
     * @param int $subjectId La id de la asignatura a editar
     */
    public static function edit($subjectId)
    {
        $app = self::$app;
        
        $subject = \Model::factory('Asignatura')
            ->where('id', $subjectId)
            ->findOne();
        
        if (!$subject) {
            self::jsonResponse(404, array(
                'error'       => 'La asignatura seleccionada no existe.',
            ));
            return;
        }
        
        $error = array();
        $subjectName = $app->request->put('nombre');
        $levelId = (int)$app->request->put('nivel');
        
        $level = \Model::factory('Nivel')
            ->where('id', $levelId)
            ->findOne();
        
        if (!$level) {
            $error[] = 'El nivel seleccionado no existe o no es válido.';
        }
        
        // Validamos los campos
        if (empty($subjectName)) {
            $error[] = 'El nombre de asignatura es obligatorio.';
        } else {
            $subjectTmp = \Model::factory('Asignatura')
                ->where('nombre', $subjectName)
                ->where('nivel_id', $levelId)
                ->whereNotEqual('id', $subject->id)
                ->findOne();
            
            if ($subjectTmp) {
                $error[] = 'El nombre de asignatura ya está en uso.';
            }
        }
        
        // Si no hay errores editamos la asignatura
        if (count($error) == 0) {
            $subject->nombre = $subjectName;
            $subject->nivel_id = $levelId;
            $subject->save();

            self::jsonResponse(200, array(
                'message'     => 'Asignatura editada correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }
    
    /**
     * Borra la asignatura seleccionada.
     *
     * @param int $subjectId La id de la asignatura a borrar
     */
    public static function delete($subjectId)
    {
        $app = self::$app;
        
        $subject = \Model::factory('Asignatura')
            ->where('id', $subjectId)
            ->findOne();

        if (!$subject) {
            self::jsonResponse(404, array(
                'error'       => 'La asignatura seleccionada no existe.',
            ));
            return;
        }
        
        if ($app->request->delete('confirm') === 'yes') {
            // Borramos la asignatura
            \Model::factory('Asignatura')
                ->where('id', $subjectId)
                ->findOne()
                ->delete();
        } else {
            self::jsonResponse(200, array(
                'deleted'     => false,
            ));
            return;
        }
        
        self::jsonResponse(200, array(
            'deleted'     => true,
            'message'     => 'Asignatura borrada correctamente.',
        ));
    }
}
