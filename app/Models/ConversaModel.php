<?php

namespace App\Models;

use CodeIgniter\Model;

class ConversaModel extends Model
{
    protected $table            = 'conversas';
    protected $primaryKey       = 'id_conversa';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = ['tipo', 'titulo', 'foto', 'criado_por'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'criado_em';
    protected $updatedField  = '';

    /**
     * Lista as conversas de um usuário para a coluna da esquerda,
     * já trazendo nome/foto exibidos, última mensagem e total de não lidas.
     */
    public function listarConversasDoUsuario(int $idUsuario): array
    {
        $sql = "
            SELECT
                c.id_conversa,
                c.tipo,
                c.titulo,
                c.foto,
                CASE WHEN c.tipo = 'individual' THEN outro.nome   ELSE c.titulo END AS nome_exibido,
                CASE WHEN c.tipo = 'individual' THEN outro.avatar ELSE c.foto   END AS foto_exibida,
                ultima.corpo      AS ultima_mensagem,
                ultima.tipo       AS ultima_mensagem_tipo,
                ultima.criado_em AS ultima_mensagem_em,
                (
                    SELECT COUNT(*)
                    FROM mensagens m2
                    WHERE m2.id_conversa = c.id_conversa
                      AND m2.id_mensagem > IFNULL(cu.ultima_mensagem_lida_id, 0)
                      AND m2.id_remetente != ?
                ) AS nao_lidas
            FROM conversas_usuarios cu
            INNER JOIN conversas c ON c.id_conversa = cu.id_conversa
            LEFT JOIN conversas_usuarios outro_cu
                   ON outro_cu.id_conversa = c.id_conversa
                  AND outro_cu.id_usuario != cu.id_usuario
                  AND c.tipo = 'individual'
            LEFT JOIN usuarios outro ON outro.id_usuario = outro_cu.id_usuario
            LEFT JOIN mensagens ultima
                   ON ultima.id_mensagem = (SELECT MAX(id_mensagem) FROM mensagens WHERE id_conversa = c.id_conversa)
            WHERE cu.id_usuario = ?
            ORDER BY ultima.criado_em DESC, c.criado_em DESC
        ";

        return $this->db->query($sql, [$idUsuario, $idUsuario])->getResultArray();
    }

    /**
     * Retorna a conversa individual entre dois usuários, criando-a se ainda não existir.
     */
    public function encontrarOuCriarConversaIndividual(int $idUsuarioA, int $idUsuarioB): int
    {
        $sql = "
            SELECT cu1.id_conversa
            FROM conversas_usuarios cu1
            INNER JOIN conversas_usuarios cu2
                    ON cu2.id_conversa = cu1.id_conversa AND cu2.id_usuario = ?
            INNER JOIN conversas c ON c.id_conversa = cu1.id_conversa
            WHERE cu1.id_usuario = ? AND c.tipo = 'individual'
            LIMIT 1
        ";

        $existente = $this->db->query($sql, [$idUsuarioB, $idUsuarioA])->getRowArray();

        if ($existente) {
            return (int) $existente['id_conversa'];
        }

        $idConversa = $this->insert([
            'tipo'       => 'individual',
            'criado_por' => $idUsuarioA,
        ]);

        $this->adicionarParticipante((int) $idConversa, $idUsuarioA);
        $this->adicionarParticipante((int) $idConversa, $idUsuarioB);

        return (int) $idConversa;
    }

    /**
     * Cria uma conversa em grupo com o criador como admin e os membros informados.
     */
    public function criarGrupo(string $titulo, int $idCriador, array $idsMembros): int
    {
        $idConversa = $this->insert([
            'tipo'       => 'grupo',
            'titulo'     => $titulo,
            'criado_por' => $idCriador,
        ]);

        $this->adicionarParticipante((int) $idConversa, $idCriador, ehAdmin: true);

        foreach ($idsMembros as $idMembro) {
            if ((int) $idMembro !== $idCriador) {
                $this->adicionarParticipante((int) $idConversa, (int) $idMembro);
            }
        }

        return (int) $idConversa;
    }

    public function adicionarParticipante(int $idConversa, int $idUsuario, bool $ehAdmin = false): void
    {
        $this->db->table('conversas_usuarios')->insert([
            'id_conversa' => $idConversa,
            'id_usuario'  => $idUsuario,
            'eh_admin'    => $ehAdmin ? 1 : 0,
            'entrou_em'   => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Confirma se o usuário faz parte da conversa (usado antes de mostrar o chat ou aceitar mensagens).
     */
    public function usuarioParticipaDaConversa(int $idConversa, int $idUsuario): bool
    {
        return (bool) $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idUsuario)
            ->countAllResults();
    }

    /**
     * Marca todas as mensagens da conversa como lidas para o usuário.
     */
    public function marcarComoLida(int $idConversa, int $idUsuario): void
    {
        $ultimoId = $this->db->table('mensagens')
            ->selectMax('id_mensagem')
            ->where('id_conversa', $idConversa)
            ->get()
            ->getRow('id_mensagem');

        if ($ultimoId === null) {
            return;
        }

        $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idUsuario)
            ->update(['ultima_mensagem_lida_id' => $ultimoId]);
    }
}
