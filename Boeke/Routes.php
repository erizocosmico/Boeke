<?php
/**
 * Boeke
 *
 * @author      José Miguel Molina <hi@mvader.me>
 * @copyright   2013 José Miguel Molina
 * @link        https://github.com/mvader/Boeke
 * @license     https://raw.github.com/mvader/Boeke/master/LICENSE
 * @version     0.9.1
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
namespace Boeke;

/**
 * Routes
 *
 * Routes proporciona métodos para añadir las rutas a la aplicación.
 *
 * @package Boeke
 * @author José Miguel Molina
 */
class Routes
{
    /**
     * Registra las rutas en la aplicación.
     *
     * @param \Slim\Slim $app Instancia de la aplicación
     */
    final public static function register($app)
    {
        // Índice
        $app->get('/', Middleware::isLoggedIn($app), '\\Boeke\\Controllers\\Index::index')
            ->name('index');
   
        // Conexión 
        $app->map('/login', Middleware::isLoggedIn($app, true), '\\Boeke\\Controllers\\Users::login')
            ->via('GET', 'POST')
            ->name('login');
    
        // Desconexión
        $app->get('/logout',Middleware::isLoggedIn($app), '\\Boeke\\Controllers\\Users::logout')
            ->name('logout');
    
        // Gestión de usuarios
        $app->group('/users', Middleware::isLoggedIn($app), function () use ($app) {
            // Listado
            $app->get('/list/(:page)', '\\Boeke\\Controllers\\Users::index')
                ->name('users_index');
            // Creación
            $app->post('/new', Middleware::isAdmin($app), '\\Boeke\\Controllers\\Users::create')
                ->name('users_new');
            // Edición
            $app->put('/edit/:id', Middleware::isAdmin($app), '\\Boeke\\Controllers\\Users::edit')
                ->name('users_edit');
            // Borrado
            $app->delete('/delete/:id', Middleware::isAdmin($app), '\\Boeke\\Controllers\\Users::delete')
                ->name('users_delete');
        });

        // Gestión de niveles
        $app->group('/levels', Middleware::isLoggedIn($app), function () use ($app) {
            // Listado
            $app->get('/list/(:page)', '\\Boeke\\Controllers\\Levels::index')
                ->name('levels_index');
            // Listado en formato JSON
            $app->get('/all', '\\Boeke\\Controllers\\Levels::getAll');
            // Creación
            $app->post('/new', '\\Boeke\\Controllers\\Levels::create');
            // Edición
            $app->put('/edit/:id', '\\Boeke\\Controllers\\Levels::edit');
            // Borrado
            $app->delete('/delete/:id', '\\Boeke\\Controllers\\Levels::delete');
        });

        // Gestión de asignaturas
        $app->group('/subjects', Middleware::isLoggedIn($app), function () use ($app) {
            // Listado
            $app->get('/list/(:page)', '\\Boeke\\Controllers\\Subjects::index')
                ->name('subjects_index'); 
            // Listado en formato JSON
            $app->get('/for_level/:level', '\\Boeke\\Controllers\\Subjects::forLevel');
            $app->get('/all', '\\Boeke\\Controllers\\Subjects::getAll');   
            // Creación
            $app->post('/new', '\\Boeke\\Controllers\\Subjects::create'); 
            // Edición
            $app->put('/edit/:id', '\\Boeke\\Controllers\\Subjects::edit');   
            // Borrado
            $app->delete('/delete/:id', '\\Boeke\\Controllers\\Subjects::delete');
        });

        // Gestión de alumnos
        $app->group('/students', Middleware::isLoggedIn($app), function () use ($app) {
            // Listado
            $app->get('/list/(:page)', '\\Boeke\\Controllers\\Students::index')
                ->name('students_index');
            // Listado en formato JSON
            $app->get('/all', '\\Boeke\\Controllers\\Students::getAll');
            // Creación
            $app->post('/new', '\\Boeke\\Controllers\\Students::create');
            // Edición
            $app->put('/edit/:id', '\\Boeke\\Controllers\\Students::edit');
            // Borrado
            $app->delete('/delete/:id', '\\Boeke\\Controllers\\Students::delete');
            // Búsqueda de alumnos
            $app->get('/search/:query', '\\Boeke\\Controllers\\Students::search');
        });

        // Gestión de libros
        $app->group('/books', Middleware::isLoggedIn($app), function () use ($app) {
            // Listado
            $app->get('/list/(:page)', '\\Boeke\\Controllers\\Books::index')
                ->name('books_index');
            // Listado en formato JSON
            $app->get('/all', '\\Boeke\\Controllers\\Books::getAll');
            // Listado por asignatura
            $app->get('/for_subject/:subject', '\\Boeke\\Controllers\\Books::forSubject');
            // Creación
            $app->post('/new', '\\Boeke\\Controllers\\Books::create');
            // Edición
            $app->put('/edit/:id', '\\Boeke\\Controllers\\Books::edit');
            // Borrado
            $app->delete('/delete/:id', '\\Boeke\\Controllers\\Books::delete');
            // Listado de libros por alumno y nivel
            $app->get(
                '/for_level/:level/for_student/:student',
                '\\Boeke\\Controllers\\Books::forLevelAndStudent'
            );
        });

        // Gestión de ejemplares
        $app->group('/copies', Middleware::isLoggedIn($app), function () use ($app) {
            // Listado
            $app->get('/list/(:page)', function ($page = 1) use ($app) {
                \Boeke\Controllers\Copies::filter('all', 'all', 0, $page);
            })->name('copies_index');
            // Creación de ejemplares
            $app->map('/create', '\\Boeke\\Controllers\\Copies::create')
                ->via('GET', 'POST')
                ->name('copies_create');
            // Edición
            $app->put('/edit/:id', '\\Boeke\\Controllers\\Copies::edit');
            // Actualizar estado
            $app->put('/update_status/:id', '\\Boeke\\Controllers\\Copies::updateStatus');
            // Borrado
            $app->delete('/delete/:id', '\\Boeke\\Controllers\\Copies::delete');
            // Filtrado de ejemplares
            $app->get('/:collection/filter_by/:type/:id/(:page)', '\\Boeke\\Controllers\\Copies::filter')
                ->name('copies_filter');
            // Préstamo de un lote de libros
            $app->map('/lending', '\\Boeke\\Controllers\\Copies::lending')
                ->via('GET', 'POST')
                ->name('copies_lending');
        });
    }
}
