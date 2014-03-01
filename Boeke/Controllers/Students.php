<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     1.0.0
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
 * Students
 *
 * Controlador para la gestión de alumnos.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Students extends Base
{
    /**
     * Muestra el listado de alumnos paginado.
     *
     * @param int $page La página actual, la 1 por defecto
     */
    public static function index($page = 1)
    {
        $app = self::$app;
        $students = array();

        // Obtenemos los registros
        $studentList = \Model::factory('Alumno')
            ->limit(25)
            ->offset(25 * ((int) $page - 1))
            ->orderByAsc('nombre')
            ->findArray();

        foreach ($studentList as $row) {
            $students[] = $row;
        }

        // Generamos la paginación para el conjunto de alumnos
        $pagination = self::generatePagination(
            \Model::factory('Alumno'),
            25,
            $page,
            function ($i) use ($app) {
                return $app->urlFor('students_index', array('page' => $i));
            }
        );

        $app->render('students_index.html.twig', array(
            'sidebar_students_active'                  => true,
            'page'                                  => $page,
            'students'                                 => $students,
            'pagination'                            => $pagination,
            'breadcrumbs'   => array(
                array(
                    'active'        => true,
                    'text'          => 'Listado de alumnos',
                    'route'         => self::$app->urlFor('students_index'),
                ),
            ),
        ));
    }

    /**
     * Devuelve en formato JSON todos los alumnos existentes.
     */
    public static function getAll()
    {
        $app = self::$app;
        $students = array_map(
            function ($student) {
                return array(
                    'nie'       => $student['nie'],
                    'name'      => $student['nombre'],
                    'surname'   => $student['apellidos'],
                    'phone'     => $student['telefono']
                );
            },
            \Model::factory('Alumno')->findArray()
        );

        self::jsonResponse(200, array(
            'students'       => $students,
        ));
    }

    /**
     * Devuelve los resultados en JSON para los alumnos que se correspondan con la búsqueda.
     *
     * @param string $query Búsqueda
     */
    public static function search($query)
    {
        $app = self::$app;
        $query .= '%';
        $students = \Model::factory('Alumno')
            ->whereRaw('nombre LIKE ? OR apellidos LIKE ?', array($query, $query))
            ->findMany();

        $studentsArray = array();
        foreach ($students as $student) {
            $studentsArray[] = array(
                'nie'       => $student->nie,
                'name'      => $student->nombre .
                    ($student->apellidos ? ' ' . $student->apellidos : ''),
            );
        }

        self::jsonResponse(200, array(
            'students'  => $studentsArray,
        ));
    }

    /**
     * Se encarga de crear un alumno.
     */
    public static function create()
    {
        $app = self::$app;
        $error = array();
        $nie = (int) $app->request->post('nie');
        $studentName = $app->request->post('nombre');
        $surnames = $app->request->post('apellidos');
        $phone = $app->request->post('telefono');

        // Validamos los posibles errores
        if (empty($studentName)) {
            $error[] = 'El nombre de alumno es obligatorio.';
        }

        if (!empty($phone)) {
            if (!preg_match('/^[0-9]{9}$/', $phone)) {
                $error[] = 'El teléfono no es válido.';
            }
        }

        if ($nie <= 0) {
            $error[] = 'El NIE introducido no es válido.';
        } else {
            $student = \Model::factory('Alumno')
                ->where('nie', $nie)
                ->findOne();

            if ($student) {
                $error[] = 'El NIE de alumno ya está en uso.';
            }
        }

        // Si no hay errores lo creamos
        if (count($error) == 0) {
            $student = \Model::factory('Alumno')->create();
            $student->nie = $nie;
            $student->nombre = $studentName;
            $student->apellidos = $surnames;
            $student->telefono = $phone;
            $student->save();

            self::jsonResponse(201, array(
                'message'       => 'Alumno creado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }

    /**
     * Se encarga de editar un alumno.
     *
     * @param int $studentId La id del alumno a editar
     */
    public static function edit($nie)
    {
        $app = self::$app;

        $student = \Model::factory('Alumno')
            ->where('nie', $nie)
            ->findOne();

        if (!$student) {
            self::jsonResponse(404, array(
                'error'       => 'El alumno seleccionado no existe.',
            ));

            return;
        }

        $error = array();
        $studentName = $app->request->post('nombre');
        $surnames = $app->request->post('apellidos', '');
        $phone = $app->request->post('telefono', '');

        // Validamos los posibles errores
        if (empty($studentName)) {
            $error[] = 'El nombre de alumno es obligatorio.';
        }

        if (!empty($phone)) {
            if (!preg_match('/^[0-9]{9}$/', $phone)) {
                $error[] = 'El teléfono no es válido.';
            }
        }

        // Si no hay errores editamos el alumno
        if (count($error) == 0) {
            $student->nombre = $studentName;
            $student->apellidos = $surnames;
            $student->telefono = $phone;
            $student->save();

            self::jsonResponse(200, array(
                'message'     => 'Alumno editado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }

    /**
     * Borra el alumno seleccionado.
     *
     * @param int $studentId La id del alumno a borrar
     */
    public static function delete($nie)
    {
        $app = self::$app;

        $student = \Model::factory('Alumno')
            ->where('nie', $nie)
            ->findOne();

        if (!$student) {
            self::jsonResponse(404, array(
                'error'       => 'El alumno seleccionado no existe.',
            ));

            return;
        }

        if ($app->request->delete('confirm') === 'yes') {
            // Borramos el alumno
            try {
                \Model::factory('Alumno')
                    ->where('nie', $nie)
                    ->findOne()
                    ->delete();
            } catch (\PDOException $e) {
                self::jsonResponse(400, array(
                    'error'       => 'No puedes borrar este alumno. Tiene ejemplares sin entregar.',
                ));

                return;
            }
        } else {
            self::jsonResponse(200, array(
                'deleted'     => false,
            ));

            return;
        }

        self::jsonResponse(200, array(
            'deleted'     => true,
            'message'     => 'Alumno borrado correctamente.',
        ));
    }
}
