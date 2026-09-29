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
     * O Postgres devolve booleanos como 't' / 'f' (texto). No JS, 'f' seria "verdadeiro",
     * então convertemos aqui pra um booleano de verdade antes de mandar pra tela.
     */
    private function paraBool($valor): bool
    {
        return $valor === true || $valor === 't' || $valor === 1 || $valor === '1' || $valor === 'true';
    }

    /**
     * Lista as conversas (já ACEITAS) do usuário para a coluna da esquerda.
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
                CASE WHEN ultima.apagada_em IS NOT NULL THEN 'Mensagem apagada' ELSE ultima.corpo END AS ultima_mensagem,
                ultima.tipo       AS ultima_mensagem_tipo,
                ultima.criado_em  AS ultima_mensagem_em,
                (
                    SELECT COUNT(*)
                    FROM mensagens m2
                    WHERE m2.id_conversa = c.id_conversa
                      AND m2.id_mensagem > COALESCE(cu.ultima_mensagem_lida_id, 0)
                      AND m2.id_remetente != ?
                      AND m2.apagada_em IS NULL
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
              AND cu.status = 'aceito'
              AND (
                    cu.oculta_em IS NULL
                 OR (ultima.criado_em IS NOT NULL AND ultima.criado_em > cu.oculta_em)
              )
            ORDER BY COALESCE(ultima.criado_em, c.criado_em) DESC
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
     * Cria o grupo. O criador entra direto como admin; os demais recebem CONVITE (pendente).
     */
    public function criarGrupo(string $titulo, int $idCriador, array $idsConvidados): int
    {
        $idConversa = $this->insert([
            'tipo'       => 'grupo',
            'titulo'     => $titulo,
            'criado_por' => $idCriador,
        ]);

        $this->adicionarParticipante((int) $idConversa, $idCriador, true);
        $this->convidarParaGrupo((int) $idConversa, $idCriador, $idsConvidados);

        return (int) $idConversa;
    }

    public function adicionarParticipante(
        int $idConversa,
        int $idUsuario,
        bool $ehAdmin = false,
        string $status = 'aceito',
        ?int $convidadoPor = null
    ): void {
        $this->db->table('conversas_usuarios')->insert([
            'id_conversa'   => $idConversa,
            'id_usuario'    => $idUsuario,
            'eh_admin'      => $ehAdmin, // booleano de verdade (Postgres não aceita 0/1 em coluna BOOLEAN)
            'status'        => $status,
            'convidado_por' => $convidadoPor,
            'entrou_em'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Cria convites pendentes. Ignora ids inválidos, o próprio convidador e quem já está
     * no grupo (aceito ou com convite pendente). Retorna quantos convites foram criados.
     */
    public function convidarParaGrupo(int $idConversa, int $idQuemConvida, array $idsUsuarios): int
    {
        $ids       = array_unique(array_map('intval', $idsUsuarios));
        $convidados = 0;

        foreach ($ids as $id) {
            if ($id <= 0 || $id === $idQuemConvida) {
                continue;
            }

            $usuarioExiste = $this->db->table('usuarios')->where('id_usuario', $id)->countAllResults();
            if (! $usuarioExiste) {
                continue;
            }

            $jaEstaNoGrupo = $this->db->table('conversas_usuarios')
                ->where('id_conversa', $idConversa)
                ->where('id_usuario', $id)
                ->countAllResults();
            if ($jaEstaNoGrupo) {
                continue;
            }

            $this->adicionarParticipante($idConversa, $id, false, 'pendente', $idQuemConvida);
            $convidados++;
        }

        return $convidados;
    }

    /**
     * Convites de grupo que o usuário recebeu e ainda não respondeu.
     */
    public function listarConvitesPendentes(int $idUsuario): array
    {
        $sql = "
            SELECT c.id_conversa, c.titulo, u.nome AS convidado_por_nome
            FROM conversas_usuarios cu
            INNER JOIN conversas c ON c.id_conversa = cu.id_conversa
            LEFT JOIN usuarios u ON u.id_usuario = cu.convidado_por
            WHERE cu.id_usuario = ? AND cu.status = 'pendente'
            ORDER BY cu.entrou_em DESC
        ";

        return $this->db->query($sql, [$idUsuario])->getResultArray();
    }

    public function aceitarConvite(int $idConversa, int $idUsuario): bool
    {
        // começa "lido" até a última mensagem existente, pra não mostrar o histórico inteiro como não lido
        $ultimoId = $this->db->table('mensagens')
            ->selectMax('id_mensagem')
            ->where('id_conversa', $idConversa)
            ->get()
            ->getRow('id_mensagem');

        $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idUsuario)
            ->where('status', 'pendente')
            ->update([
                'status'                  => 'aceito',
                'entrou_em'               => date('Y-m-d H:i:s'),
                'ultima_mensagem_lida_id' => $ultimoId,
            ]);

        return $this->db->affectedRows() > 0;
    }

    public function recusarConvite(int $idConversa, int $idUsuario): bool
    {
        $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idUsuario)
            ->where('status', 'pendente')
            ->delete();

        return $this->db->affectedRows() > 0;
    }

    /**
     * Dados da conversa vistos por um participante ACEITO (tipo, título e se ele é admin).
     * Retorna null se ele não participa.
     */
    public function detalhes(int $idConversa, int $idUsuario): ?array
    {
        $sql = "
            SELECT c.id_conversa, c.tipo, c.titulo, cu.eh_admin, cu.entrou_em
            FROM conversas c
            INNER JOIN conversas_usuarios cu ON cu.id_conversa = c.id_conversa
            WHERE c.id_conversa = ? AND cu.id_usuario = ? AND cu.status = 'aceito'
        ";

        $linha = $this->db->query($sql, [$idConversa, $idUsuario])->getRowArray();

        if (! $linha) {
            return null;
        }

        $linha['eh_admin'] = $this->paraBool($linha['eh_admin']);

        return $linha;
    }

    /**
     * Membros do grupo (inclui quem ainda está com convite pendente).
     */
    public function listarMembros(int $idConversa): array
    {
        $sql = "
            SELECT u.id_usuario, u.nome, u.avatar, cu.eh_admin, cu.status
            FROM conversas_usuarios cu
            INNER JOIN usuarios u ON u.id_usuario = cu.id_usuario
            WHERE cu.id_conversa = ?
            ORDER BY cu.status ASC, u.nome ASC
        ";

        $membros = $this->db->query($sql, [$idConversa])->getResultArray();

        foreach ($membros as &$membro) {
            $membro['eh_admin'] = $this->paraBool($membro['eh_admin']);
        }
        unset($membro);

        return $membros;
    }

    /**
     * Confirma se o usuário faz parte da conversa (só conta quem já ACEITOU).
     */
    public function usuarioParticipaDaConversa(int $idConversa, int $idUsuario): bool
    {
        return (bool) $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idUsuario)
            ->where('status', 'aceito')
            ->countAllResults();
    }

    /**
     * "Apaga" a conversa só para esse usuário (esconde da lista dele).
     * Volta a aparecer sozinha assim que chegar mensagem nova.
     */
    public function ocultarConversa(int $idConversa, int $idUsuario): void
    {
        $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idUsuario)
            ->update(['oculta_em' => date('Y-m-d H:i:s')]);
    }

    /**
     * Usuário sai do grupo. Se ele era o único admin e ainda sobrou gente,
     * promove automaticamente quem entrou há mais tempo. Se ele era o último
     * membro, apaga o grupo (e as mensagens, via ON DELETE CASCADE do banco).
     */
    public function sairDoGrupo(int $idConversa, int $idUsuario): bool
    {
        $eraAdmin = (bool) $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idUsuario)
            ->where('status', 'aceito')
            ->where('eh_admin', true)
            ->countAllResults();

        $removido = $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idUsuario)
            ->delete();

        if (! $removido || $this->db->affectedRows() === 0) {
            return false;
        }

        $restantes = $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('status', 'aceito')
            ->countAllResults();

        if ($restantes === 0) {
            $this->delete($idConversa); // apaga o grupo; mensagens somem junto (FK ON DELETE CASCADE)
            return true;
        }

        if ($eraAdmin) {
            $aindaTemAdmin = (bool) $this->db->table('conversas_usuarios')
                ->where('id_conversa', $idConversa)
                ->where('status', 'aceito')
                ->where('eh_admin', true)
                ->countAllResults();

            if (! $aindaTemAdmin) {
                $proximo = $this->db->table('conversas_usuarios')
                    ->where('id_conversa', $idConversa)
                    ->where('status', 'aceito')
                    ->orderBy('entrou_em', 'ASC')
                    ->get(1)
                    ->getRowArray();

                if ($proximo) {
                    $this->db->table('conversas_usuarios')
                        ->where('id_conversa', $idConversa)
                        ->where('id_usuario', $proximo['id_usuario'])
                        ->update(['eh_admin' => true]);
                }
            }
        }

        return true;
    }

    /**
     * Admin remove outro membro do grupo. Não permite remover a si mesmo por aqui
     * (pra isso existe sairDoGrupo) nem remover quem também é admin.
     */
    public function removerMembro(int $idConversa, int $idAdmin, int $idAlvo): bool
    {
        if ($idAdmin === $idAlvo) {
            return false;
        }

        $alvoEhAdmin = (bool) $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idAlvo)
            ->where('eh_admin', true)
            ->countAllResults();

        if ($alvoEhAdmin) {
            return false;
        }

        $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idAlvo)
            ->delete();

        return $this->db->affectedRows() > 0;
    }

    public function promoverAdmin(int $idConversa, int $idAlvo): bool
    {
        $this->db->table('conversas_usuarios')
            ->where('id_conversa', $idConversa)
            ->where('id_usuario', $idAlvo)
            ->where('status', 'aceito')
            ->update(['eh_admin' => true]);

        return $this->db->affectedRows() > 0;
    }

    public function renomearGrupo(int $idConversa, string $novoTitulo): bool
    {
        $this->update($idConversa, ['titulo' => $novoTitulo]);

        return true;
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
