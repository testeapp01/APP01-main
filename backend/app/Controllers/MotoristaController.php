<?php
namespace App\Controllers;

use App\Repositories\MotoristaRepository;
use App\Helpers\Response;

class MotoristaController
{
    private MotoristaRepository $repo;

    public function __construct(MotoristaRepository $repo)
    {
        $this->repo = $repo;
    }


    public function index(): void
    {
        $items = $this->repo->all();
        Response::json($items);
    }

    public function create(): void
    {
        $data = \App\Helpers\Request::body();
        $nome = trim((string)($data['nome'] ?? ''));
        $telefone = trim((string)($data['telefone'] ?? ''));
        $placa = trim((string)($data['placa'] ?? ''));

        if ($nome === '') {
            Response::error('Nome obrigatório', 400);
            return;
        }

        if ($telefone === '') {
            Response::error('Telefone obrigatório', 400);
            return;
        }

        if ($placa === '') {
            Response::error('Placa obrigatória', 400);
            return;
        }
        // Normalize and ensure all fields are present
        $data['cpf'] = $data['cpf'] ?? null;
        $data['placa'] = $data['placa'] ?? null;
        $data['veiculo'] = $data['veiculo'] ?? null;
        $data['uf'] = $data['uf'] ?? null;
        $data['telefone'] = $data['telefone'] ?? null;
        $data['TpCaminhao'] = $data['TpCaminhao'] ?? null;
        require_once __DIR__.'/../Helpers/Validator.php';

        if (!empty($data['cpf'])) {
            $cpfDigits = preg_replace('/\D/', '', (string)$data['cpf']);
            if (strlen($cpfDigits) !== 11 || !\Validator::validateCPF($cpfDigits)) {
                Response::error('CPF inválido', 400);
                return;
            }
            $data['cpf'] = $cpfDigits;
        }

        if ($data['telefone'] && !\Validator::validateTelefone($data['telefone'])) {
            Response::error('Telefone inválido', 400);
            return;
        }
        if (isset($data['status'])) {
            $data['status'] = $data['status'] ? 1 : 0;
        } else {
            $data['status'] = 1;
        }
        $repo = $this->repo;
        try {
            $id = $repo->create($data);
        } catch (\PDOException $e) {
            $mysqlCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
            if ($mysqlCode === 1452) {
                Response::error('Tipo de caminhão inválido.', 400);
                return;
            }

            Response::error('Falha ao salvar motorista.', 500);
            return;
        }

        http_response_code(201);
        Response::json(['id' => $id]);
    }

    public function update(int $id): void
    {
        $data = \App\Helpers\Request::body();
        $nome = trim((string)($data['nome'] ?? ''));
        $telefone = trim((string)($data['telefone'] ?? ''));
        $placa = trim((string)($data['placa'] ?? ''));

        if ($nome === '') {
            Response::error('Nome obrigatório', 400);
            return;
        }

        if ($telefone === '') {
            Response::error('Telefone obrigatório', 400);
            return;
        }

        if ($placa === '') {
            Response::error('Placa obrigatória', 400);
            return;
        }

        $data['cpf'] = $data['cpf'] ?? null;
        $data['placa'] = $placa !== '' ? $placa : null;
        $data['veiculo'] = $data['veiculo'] ?? null;
        $data['uf'] = $data['uf'] ?? null;
        $data['telefone'] = $telefone !== '' ? $telefone : null;
        $data['TpCaminhao'] = $data['TpCaminhao'] ?? null;
        $data['nome'] = $nome;

        require_once __DIR__.'/../Helpers/Validator.php';

        if (!empty($data['cpf'])) {
            $cpfDigits = preg_replace('/\D/', '', (string)$data['cpf']);
            if (strlen($cpfDigits) !== 11 || !\Validator::validateCPF($cpfDigits)) {
                Response::error('CPF inválido', 400);
                return;
            }
            $data['cpf'] = $cpfDigits;
        }

        if ($data['telefone'] && !\Validator::validateTelefone($data['telefone'])) {
            Response::error('Telefone inválido', 400);
            return;
        }

        $data['status'] = isset($data['status']) ? ($data['status'] ? 1 : 0) : 1;

        $found = $this->repo->findById($id);
        if (!$found) {
            Response::error('Motorista não encontrado', 404);
            return;
        }

        try {
            $this->repo->update($id, $data);
        } catch (\PDOException $e) {
            $mysqlCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
            if ($mysqlCode === 1452) {
                Response::error('Tipo de caminhão inválido.', 400);
                return;
            }

            Response::error('Falha ao atualizar motorista.', 500);
            return;
        }

        Response::json(['id' => $id, 'message' => 'Motorista atualizado com sucesso']);
    }

    public function listTiposCaminhao(): void
    {
        $items = $this->repo->listTiposCaminhao();
        Response::json($items);
    }

    public function delete(int $id): void
    {
        try {
            $this->repo->delete($id);
            Response::json(['message' => 'Motorista removido com sucesso']);
        } catch (\PDOException $e) {
            Response::error('Não foi possível excluir o motorista. Verifique vínculos com compras.', 409);
        }
    }
}
