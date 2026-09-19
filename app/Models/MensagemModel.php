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

    /**
     * Histórico de mensagens de uma conversa, mais antigas primeiro,
     * já com nome/avatar de quem enviou (útil em grupos).
     * $antesDoId permite paginar "carregar mensagens mais antigas".
     */
    public function historico(int $idConversa, ?int $antesDoId = null, int $limite = 30): array
    {
        $builder = $this->db->table('mensagens m')
            ->select('m.id_mensagem, m.id_conversa, m.id_remetente, m.corpo, m.anexo, m.tipo, m.criado_em, u.nome AS remetente_nome, u.avatar AS remetente_avatar')
            ->join('usuarios u', 'u.id_usuario = m.id_remetente')
            ->where('m.id_conversa', $idConversa);

        if ($antesDoId !== null) {
            $builder->where('m.id_mensagem <', $antesDoId);
        }

        $mensagens = $builder->orderBy('m.id_mensagem', 'DESC')->limit($limite)->get()->getResultArray();

        // devolve em ordem cronológica (mais antiga primeiro) pra renderizar direto na tela
        return array_reverse($mensagens);
    }

    /**
     * Usado pelo polling: busca só as mensagens novas depois do último id que o front já tem.
     */
    public function novasDesde(int $idConversa, int $ultimoIdConhecido): array
    {
        return $this->db->table('mensagens m')
            ->select('m.id_mensagem, m.id_conversa, m.id_remetente, m.corpo, m.anexo, m.tipo, m.criado_em, u.nome AS remetente_nome, u.avatar AS remetente_avatar')
            ->join('usuarios u', 'u.id_usuario = m.id_remetente')
            ->where('m.id_conversa', $idConversa)
            ->where('m.id_mensagem >', $ultimoIdConhecido)
            ->orderBy('m.id_mensagem', 'ASC')
            ->get()
            ->getResultArray();
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
}
