<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.12.3
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
 * Books
 *
 * Controlador para la gestión de libros.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Books extends Base
{
    /**
     * Comprueba si un ISBN es válido o no.
     *
     * @see http://en.wikipedia.org/wiki/International_Standard_Book_Number
     * @param  string $isbn
     * @return bool
     */
    public static function isValidISBN($isbn)
    {
        if (preg_match('/^[0-9-]{12,}[0-9]$/', $isbn)) {
            $controlDigit = $isbn[strlen($isbn) - 1];
            $isbn = substr(preg_replace('/[^\d]/', '', $isbn), 0, 12);
            $sum = 0;

            if (strlen($isbn) < 12 && !is_numeric($controlDigit)) {
                return false;
            } elseif (strlen($isbn) < 12) {
                return self::isValidIsbn($isbn . $controlDigit);
            }

            $check = 0;
            for ($i = 0; $i < 13; $i += 2) {
                $check += substr($isbn, $i, 1);
            }

            for ($i = 1; $i < 12; $i += 2) {
                $check += 3 * substr($isbn, $i, 1);
            }

            return ($check + $controlDigit) % 10 === 0;
        } elseif (preg_match('/^[0-9-]{9,}[0-9xX]$/', $isbn)) {
            $controlDigit = strtolower($isbn[strlen($isbn) - 1]);
            $isbn = substr(preg_replace('/[^\d]/', '', $isbn), 0, 9);
            $sum = 0;

            if (strlen($isbn) < 9
                || (!is_numeric($controlDigit) && $controlDigit !== 'x')) {
                return false;
            }

            for ($i = 0; $i < 9; $i++) {
                $sum += (int) $isbn[$i] * ($i + 1);
            }

            $check = $sum % 11;

            return ($check === 10 && $controlDigit === 'x')
                || ($check < 10 && $check === (int) $controlDigit);
        }

        return false;
    }

    /**
     * Muestra el listado de libros paginado.
     *
     * @param int $page La página actual, la 1 por defecto
     */
    public static function index($page = 1)
    {
        $app = self::$app;
        $books = array();
        if ($page < 1) {
            $page = 1;
        }

        // Obtenemos los registros
        $bookList = \ORM::forTable('libro')
            ->tableAlias('l')
            ->select('l.*')
            ->select('a.nombre', 'asignatura')
            ->select('n.nombre', 'nivel')
            ->join('asignatura', array('l.asignatura_id', '=', 'a.id'), 'a')
            ->join('nivel', array('a.nivel_id', '=', 'n.id'), 'n')
            ->orderByAsc('l.titulo')
            ->limit(25)
            ->offset((25 * ((int) $page - 1)))
            ->findMany();

        foreach ($bookList as $row) {
            $books[] = $row;
        }

        // Generamos la paginación para el conjunto de libros
        $pagination = self::generatePagination(
            \Model::factory('Libro'),
            25,
            $page,
            function ($i) use ($app) {
                return $app->urlFor('books_index', array('page' => $i));
            }
        );

        $app->render('books_index.html.twig', array(
            'sidebar_books_active'                  => true,
            'page'                                  => $page,
            'books'                                 => $books,
            'pagination'                            => $pagination,
            'breadcrumbs'   => array(
                array(
                    'active'        => true,
                    'text'          => 'Listado de libros',
                    'route'         => self::$app->urlFor('books_index'),
                ),
            ),
        ));
    }

    /**
     * Devuelve la lista de libros de un nivel diciendo si el estudiante
     * especificado tiene un ejemplar de dicho libro en su posesión.
     *
     * @param int $levelId   ID del nivel
     * @param int $studentId NIE del estudiante
     */
    public static function forLevelAndStudent($levelId, $studentId)
    {
        $app = self::$app;
        $books = array();
        $levelBooks = \ORM::forTable('libro')
            ->tableAlias('l')
            ->select('a.nombre', 'asignatura')
            ->select('l.*')
            ->join('asignatura', array('a.id', '=', 'l.asignatura_id'), 'a')
            ->where('a.nivel_id', $levelId)
            ->findMany();

        $userCopies = array_map(function ($copy) {
            return $copy['libro_id'];
        }, \Model::factory('Ejemplar')
            ->select('libro_id')
            ->where('alumno_nie', $studentId)
            ->findArray()
        );

        foreach ($levelBooks as $book) {
            $books[] = array(
                'subject'       => $book->asignatura,
                'title'         => $book->titulo,
                'id'            => $book->id,
                'owned'         => in_array($book->id, $userCopies),
            );
        }

        self::jsonResponse(200, array(
            'books'         => $books,
        ));
    }

    /**
     * Devuelve en formato JSON todos los libros existentes.
     */
    public static function getAll()
    {
        $app = self::$app;
        $books = array_map(
            function ($book) {
                return array(
                    'id'        => $book['id'],
                    'isbn'      => $book['isbn'],
                    'title'     => $book['titulo'],
                    'author'    => $book['autor'],
                    'year'      => $book['anio'],
                );
            },
            \Model::factory('Libro')->findArray()
        );

        self::jsonResponse(200, array(
            'books'       => $books,
        ));
    }

    /**
     * Devuelve la lista de libros para una asignatura.
     */
    public static function forSubject($subject)
    {
        $app = self::$app;
        $books = array_map(
            function ($book) {
                return array(
                    'id'        => $book['id'],
                    'name'      => $book['titulo'],
                );
            },
            \Model::factory('Libro')->where('asignatura_id', $subject)->findArray()
        );

        self::jsonResponse(200, array(
            'books'       => $books,
        ));
    }

    /**
     * Se encarga de crear un libro.
     */
    public static function create()
    {
        $app = self::$app;
        $error = array();
        $isbn = $app->request->post('isbn');
        $author = $app->request->post('autor', '');
        $title = $app->request->post('titulo');
        $year = (int) $app->request->post('anio');
        $subjectId = (int) $app->request->post('asignatura_id');

        if (empty($title)) {
            $error[] = 'El título de libro es obligatorio.';
        }

        if (empty($isbn) || !self::isValidISBN($isbn)) {
            $error[] = 'El ISBN introducido no es válido.';
        } else {
            $book = \Model::factory('Libro')
                ->where('isbn', $isbn)
                ->where('asignatura_id', $subjectId)
                ->findOne();

            if ($book) {
                $error[] = 'Ya existe este libro para la asignatura escogida.';
            }
        }

        if ($year > date('Y') || $year <= 0) {
            $error[] = 'Fecha de publicación del libro no válida.';
        }

        $subject = \Model::factory('Asignatura')
            ->findOne($subjectId);

        if (!$subject) {
            $error[] = 'La asignatura seleccionada no es válida.';
        }

        // Si no hay errores lo creamos
        if (count($error) == 0) {
            $book = \Model::factory('Libro')->create();
            $book->titulo = $title;
            $book->isbn = $isbn;
            $book->asignatura_id = $subjectId;
            $book->anio = $year;
            $book->autor = $author;
            $book->save();

            self::jsonResponse(201, array(
                'message'       => 'Libro creado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }

    /**
     * Se encarga de editar un libro.
     *
     * @param int $id La id del libro a editar
     */
    public static function edit($id)
    {
        $app = self::$app;

        $book = \Model::factory('Libro')
            ->where('id', $id)
            ->findOne();

        if (!$book) {
            self::jsonResponse(404, array(
                'error'       => 'El libro seleccionado no existe.',
            ));

            return;
        }

        $isbn = $app->request->put('isbn');
        $author = $app->request->put('autor', '');
        $title = $app->request->put('titulo');
        $year = (int) $app->request->put('anio');
        $subjectId = (int) $app->request->put('asignatura_id');

        $error = array();
        if (empty($title)) {
            $error[] = 'El título de libro es obligatorio.';
        }

        if (empty($isbn) || !self::isValidISBN($isbn)) {
            $error[] = 'El ISBN introducido no es válido.';
        } else {
            $bookTmp = \Model::factory('Libro')
                ->where('isbn', $isbn)
                ->where('asignatura_id', $subjectId)
                ->whereNotEqual('id', $id)
                ->findOne();

            if ($bookTmp) {
                $error[] = 'Ya existe este libro para la asignatura escogida.';
            }
        }

        if ($year > date('Y') || $year <= 0) {
            $error[] = 'Fecha de publicación del libro no válida.';
        }

        $subject = \Model::factory('Asignatura')
            ->findOne($subjectId);

        if (!$subject) {
            $error[] = 'La asignatura seleccionada no es válida.';
        }

        // Si no hay errores editamos el libro
        if (count($error) == 0) {
            $book->titulo = $title;
            $book->isbn = $isbn;
            $book->asignatura_id = $subjectId;
            $book->anio = $year;
            $book->autor = $author;
            $book->save();

            self::jsonResponse(200, array(
                'message'     => 'Libro editado correctamente.',
            ));
        } else {
            self::jsonResponse(400, array(
                'error'       => join('<br />', $error),
            ));
        }
    }

    /**
     * Borra el libro seleccionado.
     *
     * @param int $id La id del libro a borrar
     */
    public static function delete($id)
    {
        $app = self::$app;

        $book = \Model::factory('Libro')
            ->findOne($id);

        if (!$book) {
            self::jsonResponse(404, array(
                'error'       => 'El libro seleccionado no existe.',
            ));

            return;
        }

        if ($app->request->delete('confirm') === 'yes') {
            // Borramos el libro
            \Model::factory('Libro')
                ->findOne($id)
                ->delete();
        } else {
            self::jsonResponse(200, array(
                'deleted'     => false,
            ));

            return;
        }

        self::jsonResponse(200, array(
            'deleted'     => true,
            'message'     => 'Libro borrado correctamente.',
        ));
    }
}
