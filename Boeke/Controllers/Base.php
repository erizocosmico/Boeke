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

class Base
{
    public static $app;
    public static $config;
    
    public static function generatePagination(
        $set,
        $perPage,
        $current,
        callable $urlCallback
    ) {
        $count = $set->count();
        $pages = floor($count / $perPage);
        if ($pages != $count / $perPage) {
            $pages++;
        }
        
        if ($pages < 1) {
            return '';
        }
        
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
            // Principio
            for ($i = 1; $i < (($pages + 1) < 8 ? $pages + 1 : 8); $i++) {
                $output .= '<li' . ($current == $i ? ' class="active"' : '') .
                    '><a href="' . $urlCallback($i) . '">' . $i . '</a></li>';
            }
            
            if ($pages > (($pages) < 7 ? $pages : 7)) {
                $output .= '<li class="disabled"><span>...</span></li>';
            }
        } elseif ($pages - $current < 8) {
            // Final
            $output .= '<li class="disabled"><span>...</span></li>';
            for ($i = $pages - 7; $i <= $pages; $i++) {
                $output .= '<li' . ($current == $i ? ' class="active"' : '') .
                    '><a href="' . $urlCallback($i) . '">' . $i . '</a></li>';
            }
        } else {
            // Mitad
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
}
