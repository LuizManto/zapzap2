<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class AuthController extends BaseController
{
    protected UsuarioModel $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Mostra o formulário de cadastro.
     * Rota: GET /cadastro
     */
    public function telaCadastro()
    {
        return view('auth/registro');
    }

    /**
     * Recebe o formulário de cadastro, valida e salva no banco.
     * Rota: POST /cadastro
     */
    public function cadastrar()
    {
        // regras de validação do próprio formulário (além das regras que já
        // existem dentro do UsuarioModel, que rodam de novo no insert)
        $regras = [
            'nome'            => 'required|min_length[2]|max_length[100]',
            'email'           => 'required|valid_email|is_unique[usuarios.email]',
            'senha'           => 'required|min_length[6]',
            'confirmar_senha' => 'required|matches[senha]',
        ];

        if (! $this->validate($regras)) {
            // volta pro formulário mostrando os erros e mantendo o que já foi digitado
            return view('auth/registro', [
                'errors' => $this->validator->getErrors(),
                'old'    => $this->request->getPost(),
            ]);
        }

        $this->usuarioModel->registrar(
            $this->request->getPost('nome'),
            $this->request->getPost('email'),
            $this->request->getPost('senha')
        );

        return redirect()->to('/login')->with('sucesso', 'Cadastro feito! Agora é só entrar.');
    }

    /**
     * Mostra o formulário de login.
     * Rota: GET /login
     */
    public function telaLogin()
    {
        return view('auth/login');
    }

    /**
     * Recebe o formulário de login, confere a senha e abre a sessão.
     * Rota: POST /login
     */
    public function entrar()
    {
        $email = $this->request->getPost('email');
        $senha = $this->request->getPost('senha');

        $usuario = $this->usuarioModel->buscarPorEmail($email);

        // password_verify compara a senha digitada com o hash salvo no banco
        // (nunca comparamos senha em texto puro)
        if (! $usuario || ! password_verify($senha, $usuario['senha'])) {
            return view('auth/login', [
                'erro' => 'E-mail ou senha inválidos.',
                'old'  => ['email' => $email],
            ]);
        }

        // guarda só o essencial na sessão — nunca a senha
        session()->set([
            'usuario_logado' => true,
            'id_usuario'     => $usuario['id_usuario'],
            'nome'           => $usuario['nome'],
            'avatar'         => $usuario['avatar'],
        ]);

        return redirect()->to('/chat');
    }

    /**
     * Encerra a sessão.
     * Rota: GET /logout
     */
    public function sair()
    {
        session()->destroy();

        return redirect()->to('/login');
    }
}
