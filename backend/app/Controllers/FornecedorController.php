<?php
namespace App\Controllers;

use App\Helpers\Response;
use PDO;

class FornecedorController
{
    private ?array $fornecedorColumnsCache = null;

    private \App\Repositories\FornecedorRepository $repo;

    public function __construct(private PDO $pdo, \App\Repositories\FornecedorRepository $repo)
    {
        $this->repo = $repo;
    }

    private function hasFornecedorColumn(string $column): bool
    {
        return $this->repo->hasColumn($column);
    }

    public function index(): void
    {
        $wanted = ['id', 'razao_social', 'endereco', 'numero', 'complemento', 'bairro', 'cep', 'cidade', 'cnpj', 'email', 'telefone', 'status', 'uf'];
        $select = array_map(function (string $column): string {
            if ($this->hasFornecedorColumn($column)) {
                return $column;
            }
            if ($column === 'status') {
                return '1 AS status';
            }
            return 'NULL AS ' . $column;
        }, $wanted);

        $items = $this->repo->allSelectable($select);
        Response::json($items);
    }

    public function create(): void
    {
        $data = \App\Helpers\Request::body();
        $razaoSocial = trim((string)($data['razao_social'] ?? ''));
        $cidade = trim((string)($data['cidade'] ?? ''));
        $endereco = trim((string)($data['endereco'] ?? ''));
        $bairro = trim((string)($data['bairro'] ?? ''));
        $telefone = trim((string)($data['telefone'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $cep = trim((string)($data['cep'] ?? ''));

        if ($razaoSocial === '') {
            http_response_code(400);
            Response::json(['error' => 'Razão social obrigatória']);
            return;
        }

        if ($endereco === '' || $bairro === '' || $cidade === '') {
            http_response_code(400);
            Response::json(['error' => 'Endereço, bairro e cidade são obrigatórios']);
            return;
        }

        if ($telefone === '' || $email === '') {
            http_response_code(400);
            Response::json(['error' => 'Telefone e email são obrigatórios']);
            return;
        }

        if ($cep !== '' && strlen(preg_replace('/\D/', '', $cep)) !== 8) {
            http_response_code(400);
            Response::json(['error' => 'CEP inválido']);
            return;
        }

        if (isset($data['status'])) {
            $data['status'] = $data['status'] ? 1 : 0;
        } else {
            $data['status'] = 1;
        }
        $data['uf'] = $data['uf'] ?? null;
        $data['endereco'] = $endereco !== '' ? $endereco : null;
        $data['numero'] = $data['numero'] ?? null;
        $data['complemento'] = $data['complemento'] ?? null;
        $data['bairro'] = $bairro !== '' ? $bairro : null;
        $data['cep'] = $cep !== '' ? preg_replace('/\D/', '', $cep) : null;
        $data['cidade'] = $cidade !== '' ? $cidade : null;
        $data['razao_social'] = $razaoSocial;
        $data['telefone'] = $telefone !== '' ? $telefone : null;
        $data['email'] = $email !== '' ? $email : null;
        require_once __DIR__.'/../Helpers/Validator.php';

        if (!empty($data['cnpj'])) {
            $cnpjDigits = preg_replace('/\D/', '', (string)$data['cnpj']);
            if (strlen($cnpjDigits) !== 14 || !\Validator::validateCNPJ($cnpjDigits)) {
                http_response_code(400);
                Response::json(['error' => 'CNPJ inválido']);
                return;
            }
            $data['cnpj'] = $cnpjDigits;
        } else {
            http_response_code(400);
            Response::json(['error' => 'CNPJ obrigatório']);
            return;
        }

        if ($data['telefone'] && !\Validator::validateTelefone($data['telefone'])) {
            http_response_code(400);
            Response::json(['error' => 'Telefone inválido']);
            return;
        }

        if ($data['email'] && !\Validator::validateEmail($data['email'])) {
            http_response_code(400);
            Response::json(['error' => 'Email inválido']);
            return;
        }
        $possible = [
            'razao_social' => $data['razao_social'],
            'endereco' => $data['endereco'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cep' => $data['cep'] ?? null,
            'cidade' => $data['cidade'] ?? null,
            'cnpj' => $data['cnpj'] ?? null,
            'email' => $data['email'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'status' => $data['status'],
            'uf' => $data['uf'] ?? null,
        ];

        $insertData = array_filter(
            $possible,
            fn($value, $column) => $this->hasFornecedorColumn((string)$column),
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($insertData)) {
            http_response_code(500);
            Response::json(['error' => 'Tabela fornecedores sem colunas compatíveis para inserção.']);
            return;
        }

        try {
            $id = $this->repo->create($insertData);
        } catch (\PDOException $e) {
            if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) {
                http_response_code(409);
                Response::json(['error' => 'CNPJ já cadastrado.']);
                return;
            }
            http_response_code(500);
            Response::json(['error' => 'Falha ao salvar fornecedor.']);
            return;
        } catch (\Throwable $e) {
            http_response_code(500);
            Response::json(['error' => 'Falha ao salvar fornecedor.']);
            return;
        }

        http_response_code(201);
        Response::json(['id' => $id]);
    }

    public function update(int $id): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            Response::error('Fornecedor não encontrado', 404);
            return;
        }

        $data = \App\Helpers\Request::body();
        $razaoSocial = trim((string)($data['razao_social'] ?? ''));
        $cidade = trim((string)($data['cidade'] ?? ''));
        $endereco = trim((string)($data['endereco'] ?? ''));
        $bairro = trim((string)($data['bairro'] ?? ''));
        $telefone = trim((string)($data['telefone'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $cep = trim((string)($data['cep'] ?? ''));

        if ($razaoSocial === '') {
            Response::error('Razão social obrigatória', 400);
            return;
        }

        if ($endereco === '' || $bairro === '' || $cidade === '') {
            Response::error('Endereço, bairro e cidade são obrigatórios', 400);
            return;
        }

        if ($telefone === '' || $email === '') {
            Response::error('Telefone e email são obrigatórios', 400);
            return;
        }

        if ($cep !== '' && strlen(preg_replace('/\D/', '', $cep)) !== 8) {
            Response::error('CEP inválido', 400);
            return;
        }

        if (!empty($data['cnpj'])) {
            $cnpjDigits = preg_replace('/\D/', '', (string)$data['cnpj']);
            if (strlen($cnpjDigits) !== 14 || !\Validator::validateCNPJ($cnpjDigits)) {
                Response::error('CNPJ inválido', 400);
                return;
            }
            $data['cnpj'] = $cnpjDigits;
        } else {
            Response::error('CNPJ obrigatório', 400);
            return;
        }

        if ($data['telefone'] && !\Validator::validateTelefone($data['telefone'])) {
            Response::error('Telefone inválido', 400);
            return;
        }

        if ($data['email'] && !\Validator::validateEmail($data['email'])) {
            Response::error('Email inválido', 400);
            return;
        }

        if (isset($data['status'])) {
            $data['status'] = $data['status'] ? 1 : 0;
        } else {
            $data['status'] = $existing['status'] ?? 1;
        }
        $data['uf'] = $data['uf'] ?? $existing['uf'] ?? null;
        $data['endereco'] = $endereco !== '' ? $endereco : null;
        $data['numero'] = $data['numero'] ?? $existing['numero'] ?? null;
        $data['complemento'] = $data['complemento'] ?? $existing['complemento'] ?? null;
        $data['bairro'] = $bairro !== '' ? $bairro : null;
        $data['cep'] = $cep !== '' ? preg_replace('/\D/', '', $cep) : null;
        $data['cidade'] = $cidade !== '' ? $cidade : null;
        $data['razao_social'] = $razaoSocial;
        $data['telefone'] = $telefone !== '' ? $telefone : null;
        $data['email'] = $email !== '' ? $email : null;

        try {
            $updated = $this->repo->update($id, $data);
            if (!$updated) {
                Response::error('Fornecedor não encontrado', 404);
                return;
            }
        } catch (\Throwable) {
            Response::error('Falha ao atualizar fornecedor.', 500);
            return;
        }

        Response::json(['success' => true]);
    }

    public function delete(int $id): void
    {
        try {
            $this->repo->delete($id);
            Response::json(['message' => 'Fornecedor removido com sucesso']);
        } catch (\PDOException $e) {
            http_response_code(409);
            Response::json(['error' => 'Não foi possível excluir o fornecedor. Verifique vínculos com compras.']);
        }
    }
}
