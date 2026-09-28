<?php

namespace App\Controllers;

use App\Models\ConversaModel;
use App\Models\MensagemModel;
use App\Models\UsuarioModel;

class ChatController extends BaseController
{
    protected ConversaModel $conversaModel;
    protected MensagemModel $mensagemModel;
    protected UsuarioModel $usuarioModel;

    public function __construct()
    {
        $this->conversaModel = new ConversaModel();
        $this->mensagemModel = new MensagemModel();
        $this->usuarioModel  = new UsuarioModel();
    }

    /**
     * Tela principal (layout com as duas colunas).
     * A lista de conversas e as mensagens são carregadas depois, via JavaScript,
     * chamando os endpoints JSON abaixo.
     * Rota: GET /chat
     */
    public function index()
    {
        return view('chat/index', [
            'meuId'   => session()->get('id_usuario'),
            'meuNome' => session()->get('nome'),
        ]);
    }

    /**
     * Devolve a lista de conversas do usuário logado, em JSON.
     * Rota: GET /chat/conversas
     */
    public function conversas()
    {
        $meuId = session()->get('id_usuario');

        $lista = $this->conversaModel->listarConversasDoUsuario($meuId);

        return $this->response->setJSON($lista);
    }

    /**
     * Devolve o histórico de mensagens de uma conversa e marca como lida.
     * Rota: GET /chat/(:num)
     */
    public function abrir($idConversa)
    {
        $meuId = session()->get('id_usuario');

        // checagem de segurança: só quem participa da conversa pode ver as mensagens
        if (! $this->conversaModel->usuarioParticipaDaConversa($idConversa, $meuId)) {
            return $this->response->setStatusCode(403)->setJSON(['erro' => 'Você não participa dessa conversa.']);
        }

        $mensagens = $this->mensagemModel->historico((int) $idConversa);

        $this->conversaModel->marcarComoLida((int) $idConversa, $meuId);

        return $this->response->setJSON([
            'mensagens' => $mensagens,
            'meu_id'    => $meuId,
        ]);
    }

    /**
     * Recebe uma mensagem nova (via fetch/AJAX do JS) e salva no banco.
     * Rota: POST /chat/(:num)/enviar
     */
    public function enviar($idConversa)
    {
        $meuId = session()->get('id_usuario');

        if (! $this->conversaModel->usuarioParticipaDaConversa($idConversa, $meuId)) {
            return $this->response->setStatusCode(403)->setJSON(['erro' => 'Você não participa dessa conversa.']);
        }

        $corpo = trim((string) $this->request->getPost('corpo'));

        if ($corpo === '') {
            return $this->response->setStatusCode(422)->setJSON(['erro' => 'Mensagem vazia.']);
        }

        $idMensagem = $this->mensagemModel->enviar((int) $idConversa, $meuId, $corpo);

        return $this->response->setJSON([
            'id_mensagem' => $idMensagem,
        ]);
    }

    /**
     * Endpoint de polling: o JS chama isso a cada poucos segundos perguntando
     * "chegou mensagem nova depois da última que eu já tenho?".
     * Rota: GET /chat/(:num)/novas/(:num)
     */
    public function novas($idConversa, $ultimoIdConhecido)
    {
        $meuId = session()->get('id_usuario');

        if (! $this->conversaModel->usuarioParticipaDaConversa($idConversa, $meuId)) {
            return $this->response->setStatusCode(403)->setJSON(['erro' => 'Você não participa dessa conversa.']);
        }

        $novas = $this->mensagemModel->novasDesde((int) $idConversa, (int) $ultimoIdConhecido);

        if (! empty($novas)) {
            $this->conversaModel->marcarComoLida((int) $idConversa, $meuId);
        }

        return $this->response->setJSON($novas);
    }

    /**
     * Busca usuários por nome/e-mail, pra abrir uma conversa nova.
     * Rota: GET /chat/usuarios?q=termo
     */
    public function buscarUsuarios()
    {
        $meuId  = session()->get('id_usuario');
        $termo  = $this->request->getGet('q') ?? '';

        if (strlen($termo) < 2) {
            return $this->response->setJSON([]);
        }

        $usuarios = $this->usuarioModel->buscarUsuarios($termo, $meuId);

        return $this->response->setJSON($usuarios);
    }

    /**
     * Abre (ou acha) a conversa individual com outro usuário e devolve o id dela,
     * pra o JS já carregar o chat direto.
     * Rota: POST /chat/iniciar
     */
    public function iniciar()
    {
        $meuId    = session()->get('id_usuario');
        $outroId  = (int) $this->request->getPost('id_usuario');

        $idConversa = $this->conversaModel->encontrarOuCriarConversaIndividual($meuId, $outroId);

        return $this->response->setJSON(['id_conversa' => $idConversa]);
    }

    /**
     * Cria um grupo com os membros escolhidos.
     * Rota: POST /chat/grupo
     */
    public function criarGrupo()
    {
        $meuId       = session()->get('id_usuario');
        $titulo      = trim((string) $this->request->getPost('titulo'));
        $idsMembros  = $this->request->getPost('membros') ?? []; // array de ids vindo do form

        if ($titulo === '') {
            return $this->response->setStatusCode(422)->setJSON(['erro' => 'Dê um nome ao grupo.']);
        }

        $idConversa = $this->conversaModel->criarGrupo($titulo, $meuId, $idsMembros);

        return $this->response->setJSON(['id_conversa' => $idConversa]);
    }
}
