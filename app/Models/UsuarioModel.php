<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id_usuario';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'nome', 'email', 'senha', 'avatar', 'status',
    ];

    // só existe criado_em na tabela (sem updated_at), então desligamos o campo de update
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'criado_em';
    protected $updatedField  = '';

    protected $validationRules = [
        'nome'  => 'required|min_length[2]|max_length[100]',
        'email' => 'required|valid_email|is_unique[usuarios.email,id_usuario,{id_usuario}]',
        'senha' => 'required|min_length[6]',
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Esse e-mail já está cadastrado.',
        ],
    ];

    /**
     * Cria um usuário já fazendo o hash da senha.
     */
    public function registrar(string $nome, string $email, string $senha): int|false
    {
        return $this->insert([
            'nome'  => $nome,
            'email' => $email,
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
        ]);
    }

    /**
     * Busca um usuário pelo e-mail (usado no login).
     */
    public function buscarPorEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Busca usuários por nome ou e-mail, pra abrir uma nova conversa.
     * Exclui o próprio usuário logado da busca.
     */
    public function buscarUsuarios(string $termo, int $excetoIdUsuario): array
    {
        return $this->select('id_usuario, nome, email, avatar, status')
            ->groupStart()
                ->like('nome', $termo)
                ->orLike('email', $termo)
            ->groupEnd()
            ->where('id_usuario !=', $excetoIdUsuario)
            ->findAll(20);
    }
}
