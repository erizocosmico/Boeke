<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.12.2
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
    public static $statuses = array('Bueno', 'Regular', 'Malo', 'Perdido', 'Baja');

    /**
     * Devuelve las opciones de los estados para añadir a un campo select.
     *
     * @return string
     */
    public static function getStatusSelectOptions()
    {
        $output = '';
        foreach (self::$statuses as $i => $status) {
            $output .= '<option value="' . $i . '">' . $status . '</option>';
        }

        return $output;
    }

    /**
     * Devuelve el nombre para el código de estado seleccionado.
     *
     * @param  int    $status
     * @return string
     */
    public static function getStatusName($status)
    {
        return self::$statuses[$status];
    }

    /**
     * Muestra el listado de ejemplares paginado con filtros.
     *
     * @param string $collection Colección en la que buscar
     * @param string $type       Tipo de filtro
     * @param int    $id         Identificador para el filtro
     * @param int    $page       Página
     */
    public static function filter($collection, $type = 'all', $id = 0, $page = 1)
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

            case 'status':
                $skipQuery = !in_array($id, array_keys(self::$statuses));
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
                ->select('al.apellidos', 'alumno_apellidos')
                ->select('l.titulo', 'libro')
                ->join('libro', array('e.libro_id', '=', 'l.id'), 'l')
                ->leftOuterJoin('alumno', array('e.alumno_nie', '=', 'al.nie'), 'al');

            switch ($type) {
                case 'level':
                    $query = $query
                        ->join('asignatura', array('l.asignatura_id', '=', 'a.id'), 'a')
                        ->join('nivel', array('a.nivel_id', '=', 'n.id'), 'n')
                        ->where('n.id', $id);
                break;

                case 'subject':
                    $query = $query
                        ->join('asignatura', array('l.asignatura_id', '=', 'a.id'), 'a')
                        ->join('nivel', array('a.nivel_id', '=', 'n.id'), 'n')
                        ->where('a.id', $id);
                break;

                case 'student':
                    $query = $query->where('e.alumno_nie', $id);
                break;

                case 'status':
                    $query = $query->where('e.estado', $id);
                break;
            }
            if ($collection === 'not_returned') {
                $query = $query->whereNotNull('e.alumno_nie');
            }
            $copyCount = $query->count();
            $query = $query->orderByAsc('e.codigo')
                ->limit('25')
                ->offset((25 * ((int) $page - 1)));
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

        // Generamos la paginación para el conjunto de ejemplares
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

        $app->render('copies_index.html.twig', array(
            'sidebar_copies_active'                 => true,
            'sidebar_copies_list_active'            => $collection !== 'not_returned',
            'sidebar_copies_not_returned_active'    => $collection === 'not_returned',
            'not_returned'                          => $collection === 'not_returned',
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
                return (int) $code;
            }, $app->request->post('codigo'));
            $bookId = (int) $app->request->post('libro');
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
        $book = (int) $app->request->put('libro');
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
        $status = (int) $app->request->put('estado');
        $comment = $app->request->put('anotacion', '');
        $returned = (bool) $app->request->put('returned', false);
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
                Historial::add(
                    $copyId,
                    ($status === 3) ? 'perdido' : (($returned) ? 'devuelto' : 'actualizado'),
                    $_SESSION['user_id'],
                    $comment,
                    $copy->alumno_nie,
                    $status
                );
                if ($status === 3 || $status === 4 || $returned) {
                    $copy->alumno_nie = null;
                }
                $copy->estado = $status;
                $copy->save();
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

    /**
     * Si es accedida mediante GET muestra el formulario para el préstamo de un lote
     * de libros. Si es accedida mediante POST procesa dicha petición.
     */
    public static function lending()
    {
        $app = self::$app;

        if ($app->request->isPost()) {
            $student = (int) $app->request->post('alumno');
            $books = $app->request->post('book');
            $copies = array(
                'given'         => array(),
                'error'         => array(),
            );

            // Buscamos los libros para sacar el título
            $bookList = array();
            array_map(
                function ($book) use (&$bookList) {
                    $bookList[$book['id']] = $book['titulo'];

                    return 0;
                },
                \Model::factory('Libro')
                    ->whereIn('id', $books)
                    ->findArray()
            );

            // Buscamos los últimos libros que el alumno entregó junto con su estado
            // y su disponibilidad
            $studentCopies = \ORM::forTable('historial')
                ->tableAlias('h')
                ->select('h.estado')
                ->select('e.alumno_nie', 'disponible')
                ->select('e.codigo', 'codigo_ejemplar')
                ->select('e.libro_id', 'libro')
                ->join('ejemplar', array('e.codigo', '=', 'h.ejemplar_codigo'), 'e')
                ->where('h.alumno_nie', $student)
                ->where('h.tipo', 2)
                ->findArray();

            // Reordenamos aleatoriamente las copias para dejar al azar el estado
            // de los libros que obtendrá el alumno
            shuffle($studentCopies);
            $numCopies = count($studentCopies);
            $copyCount = 0;

            foreach ($books as $book) {
                if ($numCopies > 0) {
                    $targetCopy = null;

                    // Comprobamos si el alumno tuvo el libro
                    $didStudentOwnCopy = count(array_filter(
                        $studentCopies,
                        function ($copy) use ($book, &$targetCopy) {
                            if ($copy['libro'] == $book) {
                                $targetCopy = $copy;
                            }

                            return $copy['libro'] == $book;
                        }
                    )) > 0;

                    // Si tuvo el libro pero no está disponible lo descartamos
                    if ($didStudentOwnCopy) {
                        if (is_null($targetCopy['disponible'])) {
                            $targetCopy = null;
                        }
                    }

                    // Si lo tuvo y está disponible se lo asignamos
                    if ($targetCopy) {
                        // Asignado
                        $code = self::assignCopy(
                            $student,
                            $book,
                            null,
                            $targetCopy['codigo_ejemplar']
                        );
                        $copies[($code) ? 'given' : 'error'][] = array(
                            'code'              => $code,
                            'title'             => $bookList[$book],
                        );
                    } else {
                        if ($copyCount === $numCopies) {
                            // Seleccionamos uno de los ejemplares que tuvo el usuario
                            // para darle un libro en el mismo estado
                            $randomCopy = $studentCopies[$copyCount];
                            $code = self::assignCopy($student, $book, $randomCopy['estado']);
                            $copies[($code) ? 'given' : 'error'][] = array(
                                'code'              => $code,
                                'title'             => $bookList[$book],
                            );
                            if ($code) {
                                $copyCount++;
                            }
                        } else {
                            // Le asignamos un ejemplar cualquiera priorizando
                            // los de mejor estado porque ya se le han dado
                            // libros en el mismo estado que los que entregó
                            $code = self::assignCopy($student, $book);
                            $copies[($code) ? 'given' : 'error'][] = array(
                                'code'              => $code,
                                'title'             => $bookList[$book],
                            );
                        }
                    }
                } else {
                    // No hay registros previos en el historial sobre ejemplares
                    // previos que haya tenido el alumno así que le asignamos uno cualquiera
                    // priorizando los de mejor estado
                    $code = self::assignCopy($student, $book);
                    $copies[($code) ? 'given' : 'error'][] = array(
                        'code'              => $code,
                        'title'             => $bookList[$book],
                    );
                }
            }

            $app->render('copies_lending.html.twig', array(
                'sidebar_copies_active'                 => true,
                'sidebar_copies_lending_active'         => true,
                'results'                               => $copies,
                'breadcrumbs'                           => array(
                    array(
                        'active'        => false,
                        'text'          => 'Gestión de ejemplares',
                        'route'         => self::$app->urlFor('copies_index'),
                    ),
                    array(
                        'active'        => true,
                        'text'          => 'Entrega de un lote de libros',
                        'route'         => self::$app->urlFor('copies_lending'),
                    ),
                ),
            ));
        } else {
            $app->render('copies_lending.html.twig', array(
                'sidebar_copies_active'                 => true,
                'sidebar_copies_lending_active'         => true,
                'breadcrumbs'                           => array(
                    array(
                        'active'        => false,
                        'text'          => 'Gestión de ejemplares',
                        'route'         => self::$app->urlFor('copies_index'),
                    ),
                    array(
                        'active'        => true,
                        'text'          => 'Entrega de un lote de libros',
                        'route'         => self::$app->urlFor('copies_lending'),
                    ),
                ),
            ));
        }
    }

    /**
     * Asigna un ejemplar a un alumno basándose en el mejor estado en el que puede
     * entregarse el ejemplar.
     *
     * @param  int      $student      NIE del alumno
     * @param  int      $bookId       ID del libro
     * @param  int      $higherStatus Mejor estado en el que podemos entregar el libro
     * @param  int      $copyCode     Buscar un ejemplar específico
     * @return int|null Devuelve el código del ejemplar asignado o null si no ha podido asignarse
     */
    final private static function assignCopy(
        $student,
        $bookId,
        $higherStatus = 0,
        $copyCode = -1
    ) {
        // Si estamos buscando un ejemplar específico probamos primero con ese
        if ($copyCode < 1) {
            $copy = \Model::factory('Ejemplar')
                ->findOne($copyCode);

            if ($copy) {
                $dbh = \ORM::getDb();
                $dbh->beginTransaction();
                try {
                    $copy->alumno_nie = $student;
                    $copy->save();
                    Historial::add($copy->codigo, 'prestado', $_SESSION['user_id'], '', $student);

                    $dbh->commit();

                    return $copy->codigo;
                } catch (\PDOException $e) {
                    $dbh->rollBack();

                    return null;
                }
            }
        }

        $skip = 0;
        // En el caso de no encontrar uno empezamos a buscar por estado uno disponible.
        // Si no hay disponible para ese estado se buscará para uno peor y así sucesivamente.
        while ($higherStatus < 3) {
            $copyQuery = \Model::factory('Ejemplar')
                ->where('libro_id', $bookId)
                ->where('estado', $higherStatus)
                ->whereNull('alumno_nie')
                ->offset($skip);
            $copiesCount = $copyQuery->count();
            $copy = $copyQuery->findOne();
            if ($copy) {
                $dbh = \ORM::getDb();
                $dbh->beginTransaction();
                try {
                    $copy->alumno_nie = $student;
                    $copy->save();
                    Historial::add($copy->codigo, 'prestado', $_SESSION['user_id'], '', $student);

                    $dbh->commit();

                    return $copy->codigo;
                } catch (\PDOException $e) {
                    $dbh->rollBack();
                }
            }

            // Si quedan no quedan más copias pasamos a un estado peor
            if ($copiesCount < 2) {
                $higherStatus++;
                $skip = 0;
            } else {
                $skip++;
            }
        }

        // Si no ha habido manera de encontrar ninguno en el estado requerido
        // intentamos buscar el que sea
        $copy = \Model::factory('Ejemplar')
            ->where('libro_id', $bookId)
            ->whereNull('alumno_nie')
            ->findOne();
        if ($copy) {
            $dbh = \ORM::getDb();
            $dbh->beginTransaction();
            try {
                $copy->alumno_nie = $student;
                $copy->save();
                Historial::add($copy->codigo, 'prestado', $_SESSION['user_id'], '', $student);

                $dbh->commit();

                return $copy->codigo;
            } catch (\PDOException $e) {
                $dbh->rollBack();
            }
        }

        return null;
    }

    /**
     * Gestiona la devolución de un lote de libros.
     * Si es accedido mediante POST procesará los datos recibidos.
     * Si es accedido mediante GET simplemente mostrará el formulario.
     *
     * @param int $student NIE del alumno (solo aplicable en POST)
     */
    public static function returnStudentCopies($student = 0)
    {
        $app = self::$app;

        if ($app->request->isPost()) {
            $confirmed = (bool) $app->request->post('confirm', 0);
            // Si no ha confirmado recogemos los datos
            if (!$confirmed) {
                $student = (int) $app->request->post('student');
                $goodState = (array) $app->request->post('good_state');
                $mediumState = (array) $app->request->post('medium_state');
                $badState = (array) $app->request->post('bad_state');
            } else {
                $goodState = explode(',', $app->request->post('good_state'));
                $mediumState = explode(',', $app->request->post('medium_state'));
                $badState = explode(',', $app->request->post('bad_state'));
            }

            // Comprobamos que el alumno existe y si no existe devolvemos a la
            // pantalla de devolución de lote de libros
            if (!\Model::factory('Alumno')->findOne($student)) {
                $app->flash('error', 'El alumno especificado no existe.');
                $app->redirect($app->urlFor('copies_student_return'));

                return;
            }

            // Organizamos los nuevos estados para cada ejemplar
            $copiesNewStatus = array();
            $copiesToUpdate = array_filter(array_merge($goodState, $mediumState, $badState));
            $uniqueCopies = array_unique($copiesToUpdate);
            if (count($copiesToUpdate) !== count($uniqueCopies)) {
                $app->flash('error', 'Hay ejemplares repetidos en los diferentes estados. Por favor, inténtelo de nuevo con los datos correctos.');
                $app->redirect($app->urlFor('copies_student_return'));

                return;
            } elseif (count($copiesToUpdate) === 0) {
                $app->flash('error', 'No se han introducido ejemplares para devolver.');
                $app->redirect($app->urlFor('copies_student_return'));

                return;
            }

            foreach ($goodState as $copy) {
                $copiesNewStatus[$copy] = 0;
            }

            foreach ($mediumState as $copy) {
                $copiesNewStatus[$copy] = 1;
            }

            foreach ($badState as $copy) {
                $copiesNewStatus[$copy] = 2;
            }

            $copiesList = array();
            if (count($copiesToUpdate) > 0) {
                $copiesList = \Model::factory('Ejemplar')
                    ->whereIn('codigo', $copiesToUpdate)
                    ->findMany();
            }
            $copies = array();
            $invalidCopies = array();

            // Comprobamos que los ejemplares pertenecen al alumno
            foreach ($copiesList as $copy) {
                if ($copy->alumno_nie != $student) {
                    $invalidCopies[] = $copy;
                } else {
                    $copies[] = $copy;
                }
            }

            // Comprobamos si el usuario ha confirmado la acción
            // en cuyo caso simplemente seguiremos adelante
            if (!$confirmed) {
                // Si hay ejemplares que no pertenecen al alumno mostramos
                // el mensaje de confirmación para notificar al usuario
                if (count($invalidCopies) > 0) {
                    // Conseguimos un array con los códigos de los ejemplares que
                    // no pertenecen al usuario
                    $invalidCopies = array_map(function ($copy) {
                        return $copy->codigo;
                    }, $invalidCopies);

                    // Conseguimos un array con los códigos para cada uno de los estados
                    // tan solo de los ejemplares válidos
                    $goodState = $mediumState = $badState = array();
                    foreach ($copies as $copy) {
                        switch ($copiesNewStatus[$copy->codigo]) {
                            case 0:
                                $goodState[] = $copy->codigo;
                            break;
                            case 1:
                                $mediumState[] = $copy->codigo;
                            break;
                            case 2:
                                $badState[] = $copy->codigo;
                            break;
                        }
                    }

                    $app->render('copies_student_return.html.twig', array(
                        'confirmation'                          => true,
                        'sidebar_copies_active'                 => true,
                        'sidebar_copies_mass_return_active'     => true,
                        'good_state'                            => join(',', $goodState),
                        'medium_state'                          => join(',', $mediumState),
                        'bad_state'                             => join(',', $badState),
                        'invalid'                               => $invalidCopies,
                        'student_id'                            => $student,
                        'breadcrumbs'                           => array(
                            array(
                                'active'        => false,
                                'text'          => 'Gestión de ejemplares',
                                'route'         => self::$app->urlFor('copies_index'),
                            ),
                            array(
                                'active'        => true,
                                'text'          => 'Devolución de un lote de libros',
                                'route'         => self::$app->urlFor('copies_student_return'),
                            ),
                        ),
                    ));

                    return;
                }
            }

            // Guardamos los cambios
            if (count($copies) > 0) {
                $dbh = \ORM::getDb();
                $dbh->beginTransaction();
                try {
                    foreach ($copies as $copy) {
                        $copy->estado = $copiesNewStatus[$copy->codigo];
                        $copy->alumno_nie = null;
                        $copy->save();
                        Historial::add(
                            $copy->codigo,
                            'devuelto',
                            $_SESSION['user_id'],
                            '',
                            $student,
                            $copy->estado
                        );
                    }

                    $dbh->commit();
                    $app->flash('success', 'Ejemplares devueltos correctamente.');
                } catch (\PDOException $e) {
                    $dbh->rollBack();
                    $app->flash('error', 'Hubo un problema y no se pudieron devolver los ejemplares. Inténtelo de nuevo.');
                }
            }

            $app->redirect($app->urlFor('copies_student_return'));

            return;
        }

        $app->render('copies_student_return.html.twig', array(
            'sidebar_copies_active'                 => true,
            'sidebar_copies_mass_return_active'     => true,
            'student'                               => $student,
            'breadcrumbs'                           => array(
                array(
                    'active'        => false,
                    'text'          => 'Gestión de ejemplares',
                    'route'         => self::$app->urlFor('copies_index'),
                ),
                array(
                    'active'        => true,
                    'text'          => 'Devolución de un lote de libros',
                    'route'         => self::$app->urlFor('copies_student_return'),
                ),
            ),
        ));
    }

    /**
     * Devuelve un objeto JSON que contiene los ejemplares no devueltos de un alumno
     *
     * @param int $student NIE del alumno
     */
    public static function notReturnedByStudent($student)
    {
        $app = self::$app;

        if (!\Model::factory('Alumno')->findOne($student)) {
            self::jsonResponse(404, array(
                'error'         => 'No se ha podido encontrar al usuario seleccionado.',
            ));
        } else {
            $copiesNotReturned = \ORM::forTable('ejemplar')
                ->tableAlias('e')
                ->select('e.codigo')
                ->select('l.titulo', 'libro')
                ->join('libro', array('l.id', '=', 'e.libro_id'), 'l')
                ->where('alumno_nie', $student)
                ->findMany();

            $copies = array();
            foreach ($copiesNotReturned as $copy) {
                $copies[] = array(
                    'code'                => $copy->codigo,
                    'book'                => $copy->libro,
                );
            }

            self::jsonResponse(200, array(
                'copies'            => $copies,
            ));
        }
    }

    /**
     * Imprime la pantalla de devolución manual de libros.
     * Todas las acciones son gestionadas mediante AJAX por lo que
     * este método no efectúa ninguna otra operación.
     */
    public static function manualReturn()
    {
        $app = self::$app;

        $app->render('copies_manual_return.html.twig', array(
            'sidebar_copies_active'                 => true,
            'sidebar_copies_manual_return_active'   => true,
            'breadcrumbs'                           => array(
                array(
                    'active'        => false,
                    'text'          => 'Gestión de ejemplares',
                    'route'         => self::$app->urlFor('copies_index'),
                ),
                array(
                    'active'        => true,
                    'text'          => 'Devolución manual de libros',
                    'route'         => self::$app->urlFor('copies_manual_return'),
                ),
            ),
        ));
    }
}
