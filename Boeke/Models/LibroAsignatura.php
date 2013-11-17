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
namespace Boeke\Models;

use \Aurora\Table;
use \Aurora\Column;
use \Aurora\Relationship;
use \Aurora\ForeignKey;
use \Aurora\Types\Int;
use \Aurora\Types\String;

class Asignatura extends Table
{
    protected $libro_isbn;
    protected $asignatura_id;
    protected $asignatura;
    protected $libro;
    
    protected function setup()
    {
        $this->name = 'libro_asignatura';
        
        $this->libro_isbn = new Column(new String(13));
        $this->libro_isbn->primaryKey = true;
        $this->libro_isbn->foreignKey = new ForeignKey(
            'Libro',
            'isbn',
            'libro_isbn',
            'CASCADE',
            'CASCADE'
        );
        
        $this->asignatura_id = new Column(new Int(true));
        $this->asignatura_id->primaryKey = true;
        $this->asignatura_id->foreignKey = new ForeignKey(
            'Libro',
            'isbn',
            'libro_isbn',
            'CASCADE',
            'CASCADE'
        );
        
        $this->asignatura = new Relationship('Asignatura', 'id', 'asignatura_id', true);
        $this->libro = new Relationship('Libro', 'isbn', 'libro_isbn', true);
    }
}