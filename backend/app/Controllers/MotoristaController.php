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
        if (empty($data['nome'])) {
            http_response_code(400);
            Response::json(['error' => 'Nome obrigatório']);
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
                http_response_code(400);
                Response::json(['error' => 'CPF inválido']);
                return;
            }
            $data['cpf'] = $cpfDigits;
        }

        if ($data['telefone'] && !\Validator::validateTelefone($data['telefone'])) {
            http_response_code(400);
            Response::json(['error' => 'Telefone inválido']);
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
                http_response_code(400);
                Response::json(['error' => 'Tipo de caminhão inválido.']);
                return;
            }

            http_response_code(500);
            Response::json(['error' => 'Falha ao salvar motorista.']);
            return;
        }

        http_response_code(201);
        Response::json(['id' => $id]);
    }

    public function update(int $id): void
    {
        $data = \App\Helpers\Request::body();
        if (empty($data['nome'])) {
            http_response_code(400);
            Response::json(['error' => 'Nome obrigatório']);
            return;
        }

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
                http_response_code(400);
                Response::json(['error' => 'CPF inválido']);
                return;
            }
            $data['cpf'] = $cpfDigits;
        }

        if ($data['telefone'] && !\Validator::validateTelefone($data['telefone'])) {
            http_response_code(400);
            Response::json(['error' => 'Telefone inválido']);
            return;
        }

        $data['status'] = isset($data['status']) ? ($data['status'] ? 1 : 0) : 1;

        $found = $this->repo->findById($id);
        if (!$found) {
            http_response_code(404);
            Response::json(['error' => 'Motorista não encontrado']);
            return;
        }

        try {
            $this->repo->update($id, $data);
        } catch (\PDOException $e) {
            $mysqlCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : 0;
            if ($mysqlCode === 1452) {
                http_response_code(400);
                Response::json(['error' => 'Tipo de caminhão inválido.']);
                return;
            }

            http_response_code(500);
            Response::json(['error' => 'Falha ao atualizar motorista.']);
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
            http_response_code(409);
            Response::json(['error' => 'Não foi possível excluir o motorista. Verifique vínculos com compras.']);
        }
    }
}
