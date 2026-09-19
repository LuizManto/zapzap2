<?php

use CodeIgniter\Router\RouteCollection;

$routes->get('/login', 'AuthController::telaLogin');
$routes->post('/login', 'AuthController::entrar');
$routes->get('/cadastro', 'AuthController::telaCadastro');
$routes->post('/cadastro', 'AuthController::cadastrar');
$routes->get('/logout', 'AuthController::sair');
$routes->get('/chat', 'ChatController::index', ['filter' => 'auth']);

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
