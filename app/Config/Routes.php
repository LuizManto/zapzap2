<?php

use CodeIgniter\Router\RouteCollection;

$routes->get('/login', 'AuthController::telaLogin');
$routes->post('/login', 'AuthController::entrar');
$routes->get('/cadastro', 'AuthController::telaCadastro');
$routes->post('/cadastro', 'AuthController::cadastrar');
$routes->get('/logout', 'AuthController::sair');


$routes->get('/chat', 'ChatController::index', ['filter' => 'auth']);
$routes->get('/chat/conversas', 'ChatController::conversas', ['filter' => 'auth']);
$routes->get('/chat/usuarios', 'ChatController::buscarUsuarios', ['filter' => 'auth']);
$routes->get('/chat/convites', 'ChatController::convites', ['filter' => 'auth']);
$routes->post('/chat/iniciar', 'ChatController::iniciar', ['filter' => 'auth']);
$routes->post('/chat/grupo', 'ChatController::criarGrupo', ['filter' => 'auth']);
$routes->post('/chat/convites/(:num)/aceitar', 'ChatController::aceitarConvite/$1', ['filter' => 'auth']);
$routes->post('/chat/convites/(:num)/recusar', 'ChatController::recusarConvite/$1', ['filter' => 'auth']);
$routes->post('/chat/mensagem/(:num)/editar', 'ChatController::editarMensagem/$1', ['filter' => 'auth']);
$routes->post('/chat/mensagem/(:num)/apagar', 'ChatController::apagarMensagem/$1', ['filter' => 'auth']);
$routes->get('/chat/(:num)/novas/(:num)', 'ChatController::novas/$1/$2', ['filter' => 'auth']);
$routes->get('/chat/(:num)/membros', 'ChatController::membros/$1', ['filter' => 'auth']);
$routes->post('/chat/(:num)/enviar', 'ChatController::enviar/$1', ['filter' => 'auth']);
$routes->post('/chat/(:num)/convidar', 'ChatController::convidar/$1', ['filter' => 'auth']);
$routes->get('/chat/(:num)', 'ChatController::abrir/$1', ['filter' => 'auth']);

/** @var RouteCollection $routes */
$routes->get('/', static function () {
    return redirect()->to(session()->get('usuario_logado') ? '/chat' : '/login');
});
