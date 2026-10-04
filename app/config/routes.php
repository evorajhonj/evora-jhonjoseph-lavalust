<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
|
*/
/** @var object $router **/

lava_instance()->config->load('middleware');

$router->get('/', 'Welcome::index');
$router->get('/student', 'StudentController::index');
$router->get('/student/profile', 'StudentController::profile')
       ->middleware('student_access');
$router->get('/users', 'UsersController::index');
$router->match('/login', 'AuthController::login', 'GET|POST');
$router->get('/logout', 'AuthController::logout');
$router->get('/products', 'ProductController::index')->middleware('product_auth');
$router->match('/products/create', 'ProductController::create', 'GET|POST')->middleware('product_auth');
$router->match('/products/edit/{id}', 'ProductController::edit', 'GET|POST')
       ->where_number('id')
       ->middleware('product_auth');
$router->post('/products/delete/{id}', 'ProductController::delete')
       ->where_number('id')
       ->middleware('product_auth');

$router->get('/api', 'ApiController::index');
$router->post('/api/login', 'ApiController::login');
$router->post('/api/logout', 'ApiController::logout');
$router->get('/api/products', 'ApiController::products');
$router->post('/api/products', 'ApiController::create');
$router->match('/api/products/{id}', 'ApiController::update', 'PUT|PATCH')->where_number('id');
$router->delete('/api/products/{id}', 'ApiController::delete')->where_number('id');
$router->match('/api/{path}', 'ApiController::preflight', 'OPTIONS')->where('path', '.*');

if (PHP_SAPI === 'cli') {
    $router->get('/create-migration/{migration_class}', 'MigrationController::create_migration');
    $router->get('/migrate', 'MigrationController::migrate');
    $router->get('/rollback', 'MigrationController::rollback');
    $router->get('/rollback-all', 'MigrationController::rollback_all');
    $router->get('/refresh', 'MigrationController::refresh');
    $router->get('/status', 'MigrationController::status');
    $router->get('/seed-admin', 'MigrationController::seed_admin');
}
