<?php

namespace App\Controllers;

use App\Models\UsuarioModel;

class PerfilController extends BaseController
{
    protected UsuarioModel $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
    }

    /**
     * Rota: GET /perfil
     */
    public function tela()
    {
        $usuario = $this->usuarioModel->find(session()->get('id_usuario'));

        return view('perfil/index', ['usuario' => $usuario]);
    }

    /**
     * Rota: POST /perfil
     */
    public function atualizar()
    {
        $idUsuario = (int) session()->get('id_usuario');

        $regras = [
            'nome'  => 'required|min_length[2]|max_length[100]',
            'senha' => 'permit_empty|min_length[6]',
        ];

        if (! $this->validate($regras)) {
            $usuario = $this->usuarioModel->find($idUsuario);

            return view('perfil/index', [
                'usuario' => $usuario,
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $nome   = trim((string) $this->request->getPost('nome'));
        $status = trim((string) $this->request->getPost('status'));
        $senha  = (string) $this->request->getPost('senha');

        $nomeArquivo = $this->salvarAvatarSeEnviado();

        // salvarAvatarSeEnviado() devolve false quando o upload veio com problema
        if ($nomeArquivo === false) {
            $usuario = $this->usuarioModel->find($idUsuario);

            return view('perfil/index', [
                'usuario' => $usuario,
                'errors'  => ['avatar' => 'Envie uma imagem JPG, PNG ou GIF de até 2MB.'],
            ]);
        }

        $this->usuarioModel->atualizarPerfil(
            $idUsuario,
            $nome,
            $status,
            $nomeArquivo, // null = mantém a foto atual
            $senha !== '' ? $senha : null
        );

        // atualiza a sessão, pra header/nome já mudarem sem precisar logar de novo
        session()->set('nome', $nome);
        if ($nomeArquivo !== null) {
            session()->set('avatar', $nomeArquivo);
        }

        return redirect()->to('/perfil')->with('sucesso', 'Perfil atualizado.');
    }

    /**
     * Processa o upload de avatar, se algum arquivo tiver sido enviado.
     * Retorna: string (nome salvo) | null (nenhum arquivo enviado) | false (arquivo inválido)
     */
    private function salvarAvatarSeEnviado(): string|null|false
    {
        $arquivo = $this->request->getFile('avatar');

        if (! $arquivo || ! $arquivo->isValid() || $arquivo->getError() === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif'];
        $tamanhoMaximo    = 2 * 1024 * 1024; // 2MB

        if (! in_array($arquivo->getMimeType(), $tiposPermitidos, true) || $arquivo->getSize() > $tamanhoMaximo) {
            return false;
        }

        $novoNome = $arquivo->getRandomName();
        $arquivo->move(FCPATH . 'uploads/avatars', $novoNome);

        return $novoNome;
    }
}
