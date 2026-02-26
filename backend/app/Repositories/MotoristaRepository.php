<?php
namespace App\Repositories;

use PDO;

class MotoristaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT m.id, m.nome, m.cpf, m.placa, m.veiculo, m.uf, m.telefone, m.status, m.TpCaminhao, t.nome as tipo_caminhao FROM motoristas m LEFT JOIN tipos_caminhao t ON m.TpCaminhao = t.id ORDER BY m.id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO motoristas (nome, cpf, placa, veiculo, uf, telefone, status, TpCaminhao) VALUES (:nome, :cpf, :placa, :veiculo, :uf, :telefone, :status, :TpCaminhao)');
        $stmt->execute([
            'nome' => $data['nome'],
            'cpf' => $data['cpf'] ?? null,
            'placa' => $data['placa'] ?? null,
            'veiculo' => $data['veiculo'] ?? null,
            'uf' => $data['uf'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'status' => isset($data['status']) ? (int)$data['status'] : 1,
            'TpCaminhao' => $data['TpCaminhao'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
