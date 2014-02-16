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
namespace Boeke\Controllers;

/**
 * Base
 *
 * Controlador base que proporciona funcionalidades compartidas por el resto
 * de los controladores.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Base
{
    /**
     * @var \Slim\Slim Instancia de la aplicación
     */
    public static $app;
    /**
     * @var array Configuración del usuario
     */
    public static $config;
    
    /**
     * Genera el HTML para la paginación de un conjunto de items.
     *
     * @param \ORMWrapper $set Conjunto de items a paginar
     * @param int $perPage Cantidad de items por página
     * @param int $current Página actual
     * @param callable $urlCallback Función que dada la página genere la url para dicha página
     * @return string HTML de la paginación
     */
    final public static function generatePagination(
        $set,
        $perPage,
        $current,
        callable $urlCallback
    ) {
        if (is_numeric($set)) {
            $count = $set;
        } else {
            $count = $set->count();
        }
        $pages = ceil($count / $perPage);
        
        // Si no hay suficientes registros para paginar no devolvemos nada
        if ($count < $perPage) {
            return '';
        }
        
        // Si la página actual es mayor al número de páginas asumimos
        // que se trata de la primera
        if ($pages < $current) {
            $current = 1;
        }
        
        $output = '';
        
        if ($current > 1) {
            // Primera página
            $output .= '<li><a href="' . $urlCallback(1) .
                '">&larr; Primera</a></li>';
        }
        
        if ($current < 8 || $pages < 8) {
            // La página actual está al principio
            for ($i = 1; $i < (($pages + 1) < 8 ? $pages + 1 : 8); $i++) {
                $output .= '<li' . ($current == $i ? ' class="active"' : '') .
                    '><a href="' . $urlCallback($i) . '">' . $i . '</a></li>';
            }
            
            if ($pages > (($pages) < 7 ? $pages : 7)) {
                $output .= '<li class="disabled"><span>...</span></li>';
            }
        } elseif ($pages - $current < 8) {
            // La página actual está al final
            $output .= '<li class="disabled"><span>...</span></li>';
            for ($i = $pages - 7; $i <= $pages; $i++) {
                $output .= '<li' . ($current == $i ? ' class="active"' : '') .
                    '><a href="' . $urlCallback($i) . '">' . $i . '</a></li>';
            }
        } else {
            // La página actual está por el medio
            $output .= '<li class="disabled"><span>...</span></li>';
            for ($i = $current - 3; $i <= $current + 4; $i++) {
                $output .= '<li' . ($current == $i ? ' class="active"' : '') .
                    '><a href="' . $urlCallback($i) . '">' . $i . '</a></li>';
            }
            $output .= '<li class="disabled"><span>...</span></li>';
        }
        
        if ($current < $pages) {
            // Última página
            $output .= '<li><a href="' . $urlCallback($pages) .
                '">Última &rarr;</a></li>';
        }
        
        return $output;
    }
    
    /**
     * Envía una respuesta en formato JSON.
     *
     * @param int $status Estado de la respuesta
     * @param array $data Datos
     */
    final public static function jsonResponse($status, array $data)
    {
        self::$app->response->headers->set('Content-Type', 'application/json');
        self::$app->response()->status($status);

        echo json_encode(array_merge(array(
            'status' => $status,
        ), $data));
    }
}
