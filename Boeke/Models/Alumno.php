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
use \Aurora\Types\Int;
use \Aurora\Types\Boolean;
use \Aurora\Types\BigInt;
use \Aurora\Types\String;

class Alumno extends Table
{
    protected $nie;
    protected $nombre;
    protected $curso_actual;
    protected $repitiendo;
    protected $historial;
    
    protected function setup()
    {
        $this->name = 'alumno';
        $this->nie = new Column(new BigInt());
        $this->nie->primaryKey = true;
        $this->nombre = new Column(new String(70));
        $this->curso_actual = new Column(new Int(true));
        $this->curso_actual->default = 1;
        $this->repitiendo = new Column(new Boolean());
        $this->historial = new Relationship('Historial', 'alumno_nie', 'nie', false);
    }
}