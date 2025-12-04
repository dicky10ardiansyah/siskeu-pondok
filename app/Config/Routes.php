<?php

use CodeIgniter\Router\RouteCollection;
use App\Controllers\FinancialStatementController;

/**
 * @var RouteCollection $routes
 */

$routes->get('/develop', 'Home::develop');
$routes->get('test-write', 'TestWrite::index');
// Auth --------------------------------------------------------------------------------
$routes->get('/login', 'AuthController::login');
$routes->post('/login_process', 'AuthController::login_process');
$routes->get('/register', 'AuthController::register');
$routes->post('/save_register', 'AuthController::save_register');
$routes->get('/profile', 'AuthController::profile', ['filter' => 'auth:user,admin,superadmin']);
$routes->get('/logout', 'AuthController::logout');
$routes->get('/unauthorized', 'AuthController::unauthorized');
// Dashboard --------------------------------------------------------------------------
$routes->group('', ['filter' => 'auth:admin,user,superadmin'], function ($routes) {
    // CRUD 
    $routes->get('/', 'Home::index');
    $routes->get('/home', 'Home::index');

    $routes->get('/accounts', 'AccountController::index');
    $routes->get('/accounts/create', 'AccountController::create');
    $routes->post('/accounts/store', 'AccountController::store');
    $routes->get('/accounts/edit/(:num)', 'AccountController::edit/$1');
    $routes->post('/accounts/update/(:num)', 'AccountController::update/$1');
    $routes->post('/accounts/delete/(:num)', 'AccountController::delete/$1');

    $routes->get('journals', 'JournalsController::index');
    $routes->get('journals/create', 'JournalsController::create');
    $routes->post('journals/store', 'JournalsController::store');
    $routes->get('journals/(:num)', 'JournalsController::show/$1');
    $routes->get('journals/edit/(:num)', 'JournalsController::edit/$1');
    $routes->post('journals/update/(:num)', 'JournalsController::update/$1');
    $routes->post('journals/delete/(:num)', 'JournalsController::delete/$1');

    $routes->get('transactions', 'TransactionController::index');
    $routes->get('transactions/create', 'TransactionController::create');
    $routes->post('transactions/store', 'TransactionController::store');
    $routes->get('transactions/edit/(:num)', 'TransactionController::edit/$1');
    $routes->put('transactions/update/(:num)', 'TransactionController::update/$1');
    $routes->post('transactions/delete/(:num)', 'TransactionController::delete/$1');

    $routes->get('ledger', 'LedgerController::index');
    $routes->get('ledger/export/pdf', 'LedgerController::exportPDF');
    $routes->get('ledger/export/excel', 'LedgerController::exportExcel');

    $routes->get('/financial-statement', [FinancialStatementController::class, 'index']);
    $routes->get('/financial-statement/neraca', [FinancialStatementController::class, 'neraca']);
    $routes->get('/financial-statement/laba-rugi', [FinancialStatementController::class, 'labaRugi']);

    $routes->get('/students', 'StudentController::index');
    $routes->get('/students/create', 'StudentController::create');
    $routes->post('/students/store', 'StudentController::store');
    $routes->get('/students/edit/(:num)', 'StudentController::edit/$1');
    $routes->post('/students/update/(:num)', 'StudentController::update/$1');
    $routes->post('/students/delete/(:num)', 'StudentController::delete/$1');

    $routes->get('students/(:num)/payment-rules', 'StudentPaymentRuleController::editByStudent/$1');
    $routes->post('students/(:num)/payment-rules', 'StudentPaymentRuleController::updateByStudent/$1');
    $routes->get('students/(:num)/payment-rules/delete/(:num)', 'StudentPaymentRuleController::deleteRule/$1/$2');
    $routes->post('students/(:num)/payment-rules/add', 'StudentPaymentRuleController::addRule/$1');
    $routes->get('students/(:num)/payment-rules/disable/(:num)', 'StudentPaymentRuleController::disableRule/$1/$2');
    $routes->get('students/(:num)/payment-rules/enable/(:num)', 'StudentPaymentRuleController::enableRule/$1/$2');

    $routes->get('/billing', 'BillsController::index');
    $routes->post('/billing/generate', 'BillsController::generateBills');
    $routes->get('/billing/detail/(:num)', 'BillsController::detail/$1');
    $routes->get('/billing/pdf/(:num)', 'BillsController::pdf/$1');

    $routes->get('payments', 'PaymentsController::index');
    $routes->get('payments/create', 'PaymentsController::create');
    $routes->post('payments/store', 'PaymentsController::store');
    $routes->get('payments/edit/(:num)', 'PaymentsController::edit/$1');
    $routes->post('payments/update/(:num)', 'PaymentsController::update/$1');
    $routes->post('payments/delete/(:num)', 'PaymentsController::delete/$1');

    // Payment Categories
    $routes->get('payment-categories', 'PaymentCategoriesController::index');
    $routes->get('payment-categories/create', 'PaymentCategoriesController::create');
    $routes->post('payment-categories/store', 'PaymentCategoriesController::store');
    $routes->get('payment-categories/edit/(:num)', 'PaymentCategoriesController::edit/$1');
    $routes->post('payment-categories/update/(:num)', 'PaymentCategoriesController::update/$1');
    $routes->post('payment-categories/delete/(:num)', 'PaymentCategoriesController::delete/$1');

    $routes->get('graduates', 'GraduateController::index');
});

$routes->group('', ['filter' => 'auth:admin,superadmin'], function ($routes) {
    // CRUD
});

$routes->group('', ['filter' => 'auth:admin'], function ($routes) {
    $routes->get('/user', 'UserController::index');
    $routes->get('/user/create', 'UserController::create');
    $routes->post('/user/store', 'UserController::store');
    $routes->get('/user/edit/(:num)', 'UserController::edit/$1');
    $routes->post('/user/update/(:num)', 'UserController::update/$1');
    $routes->post('/user/delete/(:num)', 'UserController::delete/$1');
    $routes->get('/setting', 'SettingController::index');
    $routes->post('/setting/toggleRegister', 'SettingController::toggleRegister');
});
