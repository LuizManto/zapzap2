<?php

namespace App\Controllers;

use App\Models\ConversaModel;
use App\Models\MensagemModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;

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

    private function meuId(): int
    {
        return (int) session()->get('id_usuario');
    }

    private function erro(string $mensagem, int $status): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['erro' => $mensagem]);
    }

    /**
     * Tela principal (layout com as duas colunas).
     * Rota: GET /chat
     */
    public function index()
    {
        return view('chat/index', [
            'meuId'   => $this->meuId(),
            'meuNome' => session()->get('nome'),
        ]);
    }

    /**
     * Conversas (aceitas) do usuário, pra coluna da esquerda.
     * Rota: GET /chat/conversas
     */
    public function conversas()
    {
        return $this->response->setJSON($this->conversaModel->listarConversasDoUsuario($this->meuId()));
    }

    /**
     * Histórico + dados da conversa (tipo, se sou admin) e marca como lida.
     * Rota: GET /chat/(:num)
     */
    public function abrir($idConversa)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        $detalhes = $this->conversaModel->detalhes($idConversa, $meuId);
        if (! $detalhes) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        // hora do servidor guardada ANTES da consulta: o polling usa isso pra saber
        // "o que foi editado/apagado desde a última vez que eu olhei"
        $agora = date('Y-m-d H:i:s');

        // em grupo, quem entrou depois não vê o histórico de antes de entrar
        $desde = $detalhes['tipo'] === 'grupo' ? $detalhes['entrou_em'] : null;

        $mensagens = $this->mensagemModel->historico($idConversa, null, 30, $desde);
        $this->conversaModel->marcarComoLida($idConversa, $meuId);

        return $this->response->setJSON([
            'mensagens' => $mensagens,
            'meu_id'    => $meuId,
            'conversa'  => $detalhes,
            'agora'     => $agora,
        ]);
    }

    /**
     * Carrega um lote mais antigo de mensagens (botão "carregar mais" no topo do chat).
     * Rota: GET /chat/(:num)/antigas/(:num)   -- o segundo número é o id da mensagem mais antiga já carregada
     */
    public function antigas($idConversa, $antesDoId)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        $detalhes = $this->conversaModel->detalhes($idConversa, $meuId);
        if (! $detalhes) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        $desde = $detalhes['tipo'] === 'grupo' ? $detalhes['entrou_em'] : null;

        $mensagens = $this->mensagemModel->historico($idConversa, (int) $antesDoId, 30, $desde);

        return $this->response->setJSON($mensagens);
    }

    /**
     * Envia uma mensagem.
     * Rota: POST /chat/(:num)/enviar
     */
    public function enviar($idConversa)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        if (! $this->conversaModel->usuarioParticipaDaConversa($idConversa, $meuId)) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        $corpo = trim((string) $this->request->getPost('corpo'));

        if ($corpo === '') {
            return $this->erro('Mensagem vazia.', 422);
        }

        if (mb_strlen($corpo) > 5000) {
            return $this->erro('Mensagem muito longa (máximo 5000 caracteres).', 422);
        }

        $idMensagem = $this->mensagemModel->enviar($idConversa, $meuId, $corpo);

        return $this->response->setJSON(['id_mensagem' => $idMensagem]);
    }

    /**
     * Polling: devolve mensagens novas E mensagens que foram editadas/apagadas desde $desde.
     * Rota: GET /chat/(:num)/novas/(:num)?desde=YYYY-MM-DD HH:MM:SS
     */
    public function novas($idConversa, $ultimoIdConhecido)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        if (! $this->conversaModel->usuarioParticipaDaConversa($idConversa, $meuId)) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        $agora = date('Y-m-d H:i:s');
        $desde = (string) $this->request->getGet('desde');

        $novas     = $this->mensagemModel->novasDesde($idConversa, (int) $ultimoIdConhecido);
        $alteradas = [];

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $desde)) {
            $alteradas = $this->mensagemModel->alteradasDesde($idConversa, (int) $ultimoIdConhecido, $desde);
        }

        if (! empty($novas)) {
            $this->conversaModel->marcarComoLida($idConversa, $meuId);
        }

        return $this->response->setJSON([
            'novas'     => $novas,
            'alteradas' => $alteradas,
            'agora'     => $agora,
        ]);
    }

    /**
     * Edita uma mensagem (só o autor, só texto, só se não estiver apagada).
     * Rota: POST /chat/mensagem/(:num)/editar
     */
    public function editarMensagem($idMensagem)
    {
        $meuId    = $this->meuId();
        $mensagem = $this->mensagemModel->find((int) $idMensagem);

        if (! $mensagem) {
            return $this->erro('Mensagem não encontrada.', 404);
        }

        if ((int) $mensagem['id_remetente'] !== $meuId) {
            return $this->erro('Você só pode editar as suas próprias mensagens.', 403);
        }

        if (! $this->conversaModel->usuarioParticipaDaConversa((int) $mensagem['id_conversa'], $meuId)) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        if ($mensagem['apagada_em'] !== null) {
            return $this->erro('Essa mensagem já foi apagada.', 422);
        }

        if ($mensagem['tipo'] !== 'texto') {
            return $this->erro('Só é possível editar mensagens de texto.', 422);
        }

        $novoCorpo = trim((string) $this->request->getPost('corpo'));

        if ($novoCorpo === '') {
            return $this->erro('A mensagem não pode ficar vazia.', 422);
        }

        if (mb_strlen($novoCorpo) > 5000) {
            return $this->erro('Mensagem muito longa (máximo 5000 caracteres).', 422);
        }

        $this->mensagemModel->registrarEdicao((int) $idMensagem, $novoCorpo);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Apaga uma mensagem (só o autor).
     * Rota: POST /chat/mensagem/(:num)/apagar
     */
    public function apagarMensagem($idMensagem)
    {
        $meuId    = $this->meuId();
        $mensagem = $this->mensagemModel->find((int) $idMensagem);

        if (! $mensagem) {
            return $this->erro('Mensagem não encontrada.', 404);
        }

        if ((int) $mensagem['id_remetente'] !== $meuId) {
            return $this->erro('Você só pode apagar as suas próprias mensagens.', 403);
        }

        if (! $this->conversaModel->usuarioParticipaDaConversa((int) $mensagem['id_conversa'], $meuId)) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        if ($mensagem['apagada_em'] === null) {
            $this->mensagemModel->registrarExclusao((int) $idMensagem);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Busca usuários por nome/e-mail.
     * Rota: GET /chat/usuarios?q=termo
     */
    public function buscarUsuarios()
    {
        $termo = (string) ($this->request->getGet('q') ?? '');

        if (mb_strlen($termo) < 2) {
            return $this->response->setJSON([]);
        }

        return $this->response->setJSON($this->usuarioModel->buscarUsuarios($termo, $this->meuId()));
    }

    /**
     * Abre (ou acha) a conversa individual com outro usuário.
     * Rota: POST /chat/iniciar
     */
    public function iniciar()
    {
        $meuId   = $this->meuId();
        $outroId = (int) $this->request->getPost('id_usuario');

        if ($outroId <= 0 || $outroId === $meuId) {
            return $this->erro('Usuário inválido.', 422);
        }

        $idConversa = $this->conversaModel->encontrarOuCriarConversaIndividual($meuId, $outroId);

        return $this->response->setJSON(['id_conversa' => $idConversa]);
    }

    // ======================= GRUPOS E CONVITES =======================

    /**
     * Cria um grupo e envia convites (pendentes) para os membros escolhidos.
     * Rota: POST /chat/grupo   (campos: titulo, membros[])
     */
    public function criarGrupo()
    {
        $titulo  = trim((string) $this->request->getPost('titulo'));
        $membros = $this->request->getPost('membros');

        if ($titulo === '') {
            return $this->erro('Dê um nome ao grupo.', 422);
        }

        if (mb_strlen($titulo) > 150) {
            return $this->erro('Nome do grupo muito longo (máximo 150 caracteres).', 422);
        }

        if (! is_array($membros) || count($membros) === 0) {
            return $this->erro('Convide pelo menos uma pessoa.', 422);
        }

        $idConversa = $this->conversaModel->criarGrupo($titulo, $this->meuId(), $membros);

        return $this->response->setJSON(['id_conversa' => $idConversa]);
    }

    /**
     * Convites de grupo pendentes pra mim.
     * Rota: GET /chat/convites
     */
    public function convites()
    {
        return $this->response->setJSON($this->conversaModel->listarConvitesPendentes($this->meuId()));
    }

    /**
     * Rota: POST /chat/convites/(:num)/aceitar
     */
    public function aceitarConvite($idConversa)
    {
        if (! $this->conversaModel->aceitarConvite((int) $idConversa, $this->meuId())) {
            return $this->erro('Convite não encontrado.', 404);
        }

        return $this->response->setJSON(['ok' => true, 'id_conversa' => (int) $idConversa]);
    }

    /**
     * Rota: POST /chat/convites/(:num)/recusar
     */
    public function recusarConvite($idConversa)
    {
        if (! $this->conversaModel->recusarConvite((int) $idConversa, $this->meuId())) {
            return $this->erro('Convite não encontrado.', 404);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Admin do grupo convida mais gente.
     * Rota: POST /chat/(:num)/convidar   (campo: membros[])
     */
    public function convidar($idConversa)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        $detalhes = $this->conversaModel->detalhes($idConversa, $meuId);

        if (! $detalhes) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        if ($detalhes['tipo'] !== 'grupo') {
            return $this->erro('Só é possível convidar pessoas para grupos.', 422);
        }

        if (! $detalhes['eh_admin']) {
            return $this->erro('Só administradores do grupo podem convidar.', 403);
        }

        $membros = $this->request->getPost('membros');

        if (! is_array($membros) || count($membros) === 0) {
            return $this->erro('Escolha alguém para convidar.', 422);
        }

        $convidados = $this->conversaModel->convidarParaGrupo($idConversa, $meuId, $membros);

        if ($convidados === 0) {
            return $this->erro('Essa pessoa já está no grupo ou já foi convidada.', 422);
        }

        return $this->response->setJSON(['convidados' => $convidados]);
    }

    /**
     * Lista os membros do grupo (inclui convites pendentes).
     * Rota: GET /chat/(:num)/membros
     */
    public function membros($idConversa)
    {
        $idConversa = (int) $idConversa;

        if (! $this->conversaModel->usuarioParticipaDaConversa($idConversa, $this->meuId())) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        return $this->response->setJSON($this->conversaModel->listarMembros($idConversa));
    }

    /**
     * Some com a conversa só pra mim (individual ou grupo). Volta a aparecer se
     * chegar mensagem nova.
     * Rota: POST /chat/(:num)/ocultar
     */
    public function ocultarConversa($idConversa)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        if (! $this->conversaModel->usuarioParticipaDaConversa($idConversa, $meuId)) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        $this->conversaModel->ocultarConversa($idConversa, $meuId);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Sai de um grupo. Se for o único admin, promove outra pessoa automaticamente;
     * se for o último membro, o grupo é apagado.
     * Rota: POST /chat/(:num)/sair
     */
    public function sairDoGrupo($idConversa)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        $detalhes = $this->conversaModel->detalhes($idConversa, $meuId);
        if (! $detalhes) {
            return $this->erro('Você não participa dessa conversa.', 403);
        }

        if ($detalhes['tipo'] !== 'grupo') {
            return $this->erro('Essa ação é só para grupos.', 422);
        }

        $this->conversaModel->sairDoGrupo($idConversa, $meuId);

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Admin remove outro membro do grupo.
     * Rota: POST /chat/(:num)/membros/(:num)/remover
     */
    public function removerMembro($idConversa, $idAlvo)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        $detalhes = $this->conversaModel->detalhes($idConversa, $meuId);
        if (! $detalhes || $detalhes['tipo'] !== 'grupo') {
            return $this->erro('Você não participa desse grupo.', 403);
        }

        if (! $detalhes['eh_admin']) {
            return $this->erro('Só administradores podem remover membros.', 403);
        }

        if (! $this->conversaModel->removerMembro($idConversa, $meuId, (int) $idAlvo)) {
            return $this->erro('Não foi possível remover esse membro (ele pode já ser admin).', 422);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Admin promove outro membro a admin.
     * Rota: POST /chat/(:num)/membros/(:num)/promover
     */
    public function promoverAdmin($idConversa, $idAlvo)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        $detalhes = $this->conversaModel->detalhes($idConversa, $meuId);
        if (! $detalhes || $detalhes['tipo'] !== 'grupo') {
            return $this->erro('Você não participa desse grupo.', 403);
        }

        if (! $detalhes['eh_admin']) {
            return $this->erro('Só administradores podem promover outros membros.', 403);
        }

        if (! $this->conversaModel->promoverAdmin($idConversa, (int) $idAlvo)) {
            return $this->erro('Não foi possível promover esse membro.', 422);
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Admin renomeia o grupo.
     * Rota: POST /chat/(:num)/renomear   (campo: titulo)
     */
    public function renomearGrupo($idConversa)
    {
        $idConversa = (int) $idConversa;
        $meuId      = $this->meuId();

        $detalhes = $this->conversaModel->detalhes($idConversa, $meuId);
        if (! $detalhes || $detalhes['tipo'] !== 'grupo') {
            return $this->erro('Você não participa desse grupo.', 403);
        }

        if (! $detalhes['eh_admin']) {
            return $this->erro('Só administradores podem renomear o grupo.', 403);
        }

        $novoTitulo = trim((string) $this->request->getPost('titulo'));

        if ($novoTitulo === '') {
            return $this->erro('O nome do grupo não pode ficar vazio.', 422);
        }

        if (mb_strlen($novoTitulo) > 150) {
            return $this->erro('Nome do grupo muito longo (máximo 150 caracteres).', 422);
        }

        $this->conversaModel->renomearGrupo($idConversa, $novoTitulo);

        return $this->response->setJSON(['ok' => true, 'titulo' => $novoTitulo]);
    }
}
