<?php

namespace App\Models;

use CodeIgniter\Model;

class MensagemModel extends Model
{
    protected $table            = 'mensagens';
    protected $primaryKey       = 'id_mensagem';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = ['id_conversa', 'id_remetente', 'corpo', 'anexo', 'tipo'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'criado_em';
    protected $updatedField  = '';

    protected $validationRules = [
        'id_conversa'  => 'required|integer',
        'id_remetente' => 'required|integer',
        'corpo'        => 'permit_empty|max_length[5000]',
    ];

    private const COLUNAS = 'm.id_mensagem, m.id_conversa, m.id_remetente, m.corpo, m.anexo, m.tipo, m.criado_em,
                             m.editada_em, m.apagada_em, u.nome AS remetente_nome, u.avatar AS remetente_avatar';

    /**
     * Converte editada_em / apagada_em em flags simples pro JS (editada / apagada)
     * e garante que o conteúdo de mensagem apagada nunca sai do servidor.
     */
    private function formatar(array $linhas): array
    {
        foreach ($linhas as &$m) {
            $m['apagada'] = $m['apagada_em'] !== null;
            $m['editada'] = $m['editada_em'] !== null;

            if ($m['apagada']) {
                $m['corpo']  = null;
                $m['anexo']  = null;
            }

            unset($m['apagada_em'], $m['editada_em']);
        }
        unset($m);

        return $linhas;
    }

    /**
     * Histórico de mensagens de uma conversa, mais antigas primeiro.
     */
    public function historico(int $idConversa, ?int $antesDoId = null, int $limite = 30): array
    {
        $builder = $this->db->table('mensagens m')
            ->select(self::COLUNAS)
            ->join('usuarios u', 'u.id_usuario = m.id_remetente')
            ->where('m.id_conversa', $idConversa);

        if ($antesDoId !== null) {
            $builder->where('m.id_mensagem <', $antesDoId);
        }

        $mensagens = $builder->orderBy('m.id_mensagem', 'DESC')->limit($limite)->get()->getResultArray();

        return $this->formatar(array_reverse($mensagens));
    }

    /**
     * Polling: mensagens NOVAS (id maior que o último que a tela já tem).
     */
    public function novasDesde(int $idConversa, int $ultimoIdConhecido): array
    {
        $linhas = $this->db->table('mensagens m')
            ->select(self::COLUNAS)
            ->join('usuarios u', 'u.id_usuario = m.id_remetente')
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
        $linhas = $this->db->table('mensagens m')
            ->select(self::COLUNAS)
            ->join('usuarios u', 'u.id_usuario = m.id_remetente')
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

    public function enviar(int $idConversa, int $idRemetente, string $corpo, string $tipo = 'texto', ?string $anexo = null): int|false
    {
        return $this->insert([
            'id_conversa'  => $idConversa,
            'id_remetente' => $idRemetente,
            'corpo'        => $corpo,
            'tipo'         => $tipo,
            'anexo'        => $anexo,
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
                'apagada_em' => date('Y-m-d H:i:s'),
            ]);
    }
}
