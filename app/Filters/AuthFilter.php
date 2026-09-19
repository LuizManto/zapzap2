<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Roda ANTES do controller. Se não tiver usuário logado na sessão,
     * manda pra tela de login em vez de deixar acessar a página pedida.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('usuario_logado')) {
            return redirect()->to('/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // nada a fazer depois da resposta
    }
}
