<?php

namespace App\Models;

use CodeIgniter\Model;

class MensagemModel extends Model
{
    protected $table            = 'mensagens';
    protected $primaryKey       = 'id_mensagem';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = ['id_conversa', 'id_remetente', 'corpo', 'anexo', 'anexo_nome', 'tipo', 'respondendo_a'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'criado_em';
    protected $updatedField  = '';

    protected $validationRules = [
        'id_conversa'  => 'required|integer',
        'id_remetente' => 'required|integer',
        'corpo'        => 'permit_empty|max_length[5000]',
    ];

    // "mq"/"uq" = mensagem/usuário citados (a mensagem que está sendo respondida)
    private const COLUNAS = "
        m.id_mensagem, m.id_conversa, m.id_remetente, m.corpo, m.anexo, m.anexo_nome, m.tipo, m.criado_em,
        m.editada_em, m.apagada_em, m.respondendo_a,
        u.nome AS remetente_nome, u.avatar AS remetente_avatar,
        mq.corpo AS resposta_corpo, mq.tipo AS resposta_tipo, mq.apagada_em AS resposta_apagada_em,
        uq.nome AS resposta_remetente_nome
    ";

    private function juntarTabelas($builder)
    {
        return $builder
            ->join('usuarios u', 'u.id_usuario = m.id_remetente')
            ->join('mensagens mq', 'mq.id_mensagem = m.respondendo_a', 'left')
            ->join('usuarios uq', 'uq.id_usuario = mq.id_remetente', 'left');
    }

    /**
     * Converte editada_em / apagada_em em flags simples pro JS, garante que o conteúdo
     * de mensagem apagada nunca sai do servidor, e monta um objeto "resposta_a" (ou null)
     * com um resumo da mensagem citada.
     */
    private function formatar(array $linhas): array
    {
        foreach ($linhas as &$m) {
            $m['apagada'] = $m['apagada_em'] !== null;
            $m['editada'] = $m['editada_em'] !== null;

            if ($m['apagada']) {
                $m['corpo']       = null;
                $m['anexo']       = null;
                $m['anexo_nome']  = null;
            }

            if ($m['respondendo_a'] !== null) {
                $respostaApagada = $m['resposta_apagada_em'] !== null;

                $m['resposta_a'] = [
                    'id_mensagem'    => $m['respondendo_a'],
                    'remetente_nome' => $m['resposta_remetente_nome'],
                    'corpo'          => $respostaApagada ? null : $m['resposta_corpo'],
                    'tipo'           => $m['resposta_tipo'],
                    'apagada'        => $respostaApagada,
                ];
            } else {
                $m['resposta_a'] = null;
            }

            unset(
                $m['editada_em'], $m['apagada_em'], $m['respondendo_a'],
                $m['resposta_corpo'], $m['resposta_tipo'], $m['resposta_apagada_em'], $m['resposta_remetente_nome']
            );
        }
        unset($m);

        return $linhas;
    }

    /**
     * Histórico de mensagens de uma conversa, mais antigas primeiro.
     * $antesDoId pagina pra trás ("carregar mensagens mais antigas").
     * $desde (opcional) esconde mensagens de antes dessa data — usado pra quem
     * entrou num grupo não ver o histórico anterior à entrada dele.
     */
    public function historico(int $idConversa, ?int $antesDoId = null, int $limite = 30, ?string $desde = null): array
    {
        $builder = $this->juntarTabelas(
            $this->db->table('mensagens m')->select(self::COLUNAS)
        )->where('m.id_conversa', $idConversa);

        if ($antesDoId !== null) {
            $builder->where('m.id_mensagem <', $antesDoId);
        }

        if ($desde !== null) {
            $builder->where('m.criado_em >=', $desde);
        }

        $mensagens = $builder->orderBy('m.id_mensagem', 'DESC')->limit($limite)->get()->getResultArray();

        return $this->formatar(array_reverse($mensagens));
    }

    /**
     * Polling: mensagens NOVAS (id maior que o último que a tela já tem).
     */
    public function novasDesde(int $idConversa, int $ultimoIdConhecido): array
    {
        $linhas = $this->juntarTabelas(
            $this->db->table('mensagens m')->select(self::COLUNAS)
        )
            ->where('m.id_conversa', $idConversa)
            ->where('m.id_mensagem >', $ultimoIdConhecido)
            ->orderBy('m.id_mensagem', 'ASC')
            ->get()
            ->getResultArray();

        return $this->formatar($linhas);
    }

    /**
     * Polling: mensagens que a tela JÁ TEM, mas que foram editadas ou apagadas depois de $desde.
     */
    public function alteradasDesde(int $idConversa, int $ultimoIdConhecido, string $desde): array
    {
        $linhas = $this->juntarTabelas(
            $this->db->table('mensagens m')->select(self::COLUNAS)
        )
            ->where('m.id_conversa', $idConversa)
            ->where('m.id_mensagem <=', $ultimoIdConhecido)
            ->groupStart()
                ->where('m.editada_em >=', $desde)
                ->orWhere('m.apagada_em >=', $desde)
            ->groupEnd()
            ->orderBy('m.id_mensagem', 'ASC')
            ->get()
            ->getResultArray();

        return $this->formatar($linhas);
    }

    /**
     * Confirma que a mensagem citada existe, pertence à mesma conversa e não está apagada.
     * Usado antes de salvar uma resposta, pra não deixar citar mensagem de outra conversa.
     */
    public function podeResponder(int $idConversa, int $idMensagem): bool
    {
        return (bool) $this->db->table('mensagens')
            ->where('id_mensagem', $idMensagem)
            ->where('id_conversa', $idConversa)
            ->where('apagada_em', null)
            ->countAllResults();
    }

    public function enviar(
        int $idConversa,
        int $idRemetente,
        ?string $corpo,
        string $tipo = 'texto',
        ?string $anexo = null,
        ?string $anexoNome = null,
        ?int $respondendoA = null
    ): int|false {
        return $this->insert([
            'id_conversa'   => $idConversa,
            'id_remetente'  => $idRemetente,
            'corpo'         => $corpo,
            'tipo'          => $tipo,
            'anexo'         => $anexo,
            'anexo_nome'    => $anexoNome,
            'respondendo_a' => $respondendoA,
        ]);
    }

    public function registrarEdicao(int $idMensagem, string $novoCorpo): void
    {
        $this->db->table('mensagens')
            ->where('id_mensagem', $idMensagem)
            ->update([
                'corpo'      => $novoCorpo,
                'editada_em' => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * "Apagar" de verdade o conteúdo, mas mantendo a linha (pra manter a ordem da conversa
     * e mostrar "Mensagem apagada" pra todo mundo).
     */
    public function registrarExclusao(int $idMensagem): void
    {
        $this->db->table('mensagens')
            ->where('id_mensagem', $idMensagem)
            ->update([
                'corpo'      => null,
                'anexo'      => null,
                'anexo_nome' => null,
                'apagada_em' => date('Y-m-d H:i:s'),
            ]);
    }
}
