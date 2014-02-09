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
 * Levels
 *
 * Controlador para la gestión de niveles.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Levels extends Base
{  
    /**
     * Muestra el listado de niveles paginado.
     *
     * @param int $page La página actual, la 1 por defecto
     */
    public static function index($page = 1)
    {
        $app = self::$app;
        $levels = array();
        
        // Obtenemos los registros
        $levelList = \Model::factory('Nivel')
            ->limit(25)
            ->offset(25 * ((int)$page - 1))
            ->orderByAsc('id')
            ->findArray();
        
        foreach ($levelList as $row) {
            $levels[] = $row;
        }
        
        // Generamos la paginación para el conjunto de niveles
        $pagination = self::generatePagination(
            \Model::factory('Nivel'),
            25,
            $page,
            function ($i) use ($app) {
                return $app->urlFor('levels_index', array('page' => $i));
            }
        );
        
        $app->render('levels_index.html.twig', array(
            'sidebar_levels_active'                  => true,
            'page'                                  => $page,
            'levels'                                 => $levels,
            'pagination'                            => $pagination,
            'breadcrumbs'   => array(
                array(
                    'active'        => true,
                    'text'          => 'Listado de niveles',
                    'route'         => self::$app->urlFor('levels_index'),
                ),
            ),
        ));
    }
    
    /**
     * Devuelve en formato JSON todos los niveles existentes.
     */
    public static function getAll()
    {
        $app = self::$app;
        $levels = array_map(
            function ($level) {
                return array(
                    'id'    => $level['id'],
                    'name'  => $level['nombre'],
                );
            },
            \Model::factory('Nivel')->findArray()
        );
        
        self::jsonResponse(200, array(
            'levels'       => $levels,
        ));
    }
    
    /**
     * Se encarga de crear un nivel.
     */
    public static function create()
    {
        $app = self::$app;
        $error = array();
        $levelName = $app->request->post('nombre');

        // Validamos los posibles errores
        if (empty($levelName)) {
            $error[] = 'El nombre de nivel es obligatorio.';
        } else {
            $level = \Model::factory('Nivel')
                ->where('nombre', $levelName)
                ->findOne();
            
            if ($level) {
                $error[] = 'El nombre de nivel ya está en uso.';
            }
        }
        
        // Si no hay errores lo creamos
        if (count($error) == 0) {
            $level = \Model::factory('Nivel')->create();
            $level->nombre = $levelName;
            $level->save();
            
            self::jsonResponse(201, array(
                'message'       => 'Nivel creado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }
    
    /**
     * Se encarga de editar un nivel.
     *
     * @param int $levelId La id del nivel a editar
     */
    public static function edit($levelId)
    {
        $app = self::$app;
        
        $level = \Model::factory('Nivel')
            ->where('id', $levelId)
            ->findOne();

        if (!$level) {
            self::jsonResponse(404, array(
                'error'       => 'El nivel seleccionado no existe.',
            ));
            return;
        }
        
        $error = array();
        $levelName = $app->request->put('nombre');
        
        // Validamos los campos
        if (empty($levelName)) {
            $error[] = 'El nombre de nivel es obligatorio.';
        } else {
            $levelTmp = \Model::factory('Nivel')
                ->where('nombre', $levelName)
                ->whereNotEqual('id', $level->id)
                ->findOne();
            
            if ($levelTmp) {
                $error[] = 'El nombre de nivel ya está en uso.';
            }
        }
        
        // Si no hay errores editamos el nivel
        if (count($error) == 0) {
            $level->nombre = $levelName;
            $level->save();

            self::jsonResponse(200, array(
                'message'     => 'Nivel editado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }
    
    /**
     * Borra el nivel seleccionado.
     *
     * @param int $levelId La id del nivel a borrar
     */
    public static function delete($levelId)
    {
        $app = self::$app;
        
        $level = \Model::factory('Nivel')
            ->where('id', $levelId)
            ->findOne();

        if (!$level) {
            self::jsonResponse(404, array(
                'error'       => 'El nivel seleccionado no existe.',
            ));
            return;
        }
        
        if ($app->request->delete('confirm') === 'yes') {
            // Borramos el nivel
            \Model::factory('Nivel')
                ->where('id', $levelId)
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
            'message'     => 'Nivel borrado correctamente.',
        ));
    }
}
