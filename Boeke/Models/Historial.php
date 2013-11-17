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
use \Aurora\Types\BigInt;
use \Aurora\Types\Blob;

class Historial extends Table
{
    protected $id;
    protected $tipo;
    protected $ejemplar_codigo;
    protected $alumno_nie;
    protected $estado;
    protected $fecha;
    protected $anotacion;
    protected $ejemplar;
    protected $alumno;
    
    protected function setup()
    {
        $this->name = 'historial';
        
        $this->id = new Column(new Int(true));
        $this->id->primaryKey = true;
        $this->tipo = new Column(new Int(true));
        $this->tipo->default = 0;
        $this->ejemplar_codigo = new Column(new Int(true));
        $this->ejemplar_codigo->foreignKey = new ForeignKey(
            'Ejemplar',
            'codigo',
            'ejemplar_codigo',
            'CASCADE',
            'CASCADE'
        );
        $this->alumno_nie = new Column(new BigInt());
        $this->alumno_nie->foreignKey = new ForeignKey(
            'Alumno',
            'nie',
            'alumno_nie',
            'NO ACTION',
            'NO ACTION'
        );
        $this->estado = new Column(new Int(true));
        $this->fecha = new Column(new BigInt());
        $this->anotacion = new Column(new Blob());
        
        $this->ejemplar = new Relationship(
            'Ejemplar',
            'codigo',
            'ejemplar_codigo',
            true
        );
        $this->alumno = new Relationship(
            'Alumno',
            'nie',
            'alumno_nie',
            true
        );
    }
}