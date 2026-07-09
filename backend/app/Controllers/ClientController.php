<?php
namespace App\Controllers;

use App\Repositories\ClientRepository;
use App\Helpers\SchemaValidator;
use App\Helpers\Response;
use PDO;

class ClientController
{
    private ClientRepository $repo;

    public function __construct(private PDO $pdo, ClientRepository $repo)
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
        require_once __DIR__.'/../Helpers/Validator.php';

        $data = \App\Helpers\Request::body();
        $errors = SchemaValidator::validate($data, [
            'required' => ['nome'],
            'properties' => [
                'nome' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 120],
                'telefone' => ['type' => 'string', 'minLength' => 10, 'maxLength' => 20],
                'email' => ['type' => 'string', 'format' => 'email', 'maxLength' => 120],
                'cidade' => ['type' => 'string', 'maxLength' => 120],
                'uf' => ['type' => 'string', 'maxLength' => 2],
            ],
        ]);
        if (!empty($errors)) {
            http_response_code(422);
            Response::json(['error' => 'Payload inválido', 'details' => $errors]);
            return;
        }

        if (empty($data['nome'])) {
            http_response_code(400);
            Response::json(['error' => 'Nome obrigatório']);
            return;
        }
        // Normalize status, uf, and ensure all fields are present
        $data['endereco'] = $data['endereco'] ?? null;
        $data['numero'] = $data['numero'] ?? null;
        $data['complemento'] = $data['complemento'] ?? null;
        $data['bairro'] = $data['bairro'] ?? null;
        $data['cep'] = $data['cep'] ?? null;
        $data['cpf_cnpj'] = $data['cpf_cnpj'] ?? null;
        $data['telefone'] = $data['telefone'] ?? null;
        $data['email'] = $data['email'] ?? null;
        $data['uf'] = $data['uf'] ?? null;
        $data['cidade'] = $data['cidade'] ?? null;

        if ($data['cpf_cnpj']) {
            $cpfCnpjDigits = preg_replace('/\D/', '', (string)$data['cpf_cnpj']);
            if (strlen($cpfCnpjDigits) !== 11 && strlen($cpfCnpjDigits) !== 14) {
                http_response_code(400);
                Response::json(['error' => 'CPF/CNPJ inválido']);
                return;
            }

            if (strlen($cpfCnpjDigits) === 11 && !\Validator::validateCPF($cpfCnpjDigits)) {
                http_response_code(400);
                Response::json(['error' => 'CPF inválido']);
                return;
            }

            if (strlen($cpfCnpjDigits) === 14 && !\Validator::validateCNPJ($cpfCnpjDigits)) {
                http_response_code(400);
                Response::json(['error' => 'CNPJ inválido']);
                return;
            }

            if ($this->repo->hasCpfCnpj($cpfCnpjDigits)) {
                http_response_code(409);
                Response::json([
                    'error' => strlen($cpfCnpjDigits) === 11
                        ? 'CPF já cadastrado.'
                        : 'CNPJ já cadastrado.'
                ]);
                return;
            }

            $data['cpf_cnpj'] = $cpfCnpjDigits;
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
        if (isset($data['status'])) {
            $data['status'] = $data['status'] ? '1' : '0';
        } else {
            $data['status'] = '1';
        }
        $id = $this->repo->create($data);
        http_response_code(201);
        Response::json(['id' => $id]);
    }

    public function update(int $id): void
    {
        require_once __DIR__.'/../Helpers/Validator.php';

        if (!$this->repo->findById($id)) {
            http_response_code(404);
            Response::json(['error' => 'Cliente não encontrado']);
            return;
        }

        $data = \App\Helpers\Request::body();
        $fields = [];

        if (array_key_exists('nome', $data)) {
            $nome = trim((string)($data['nome'] ?? ''));
            if ($nome === '') {
                http_response_code(400);
                Response::json(['error' => 'Nome obrigatório']);
                return;
            }
            $fields['nome'] = $nome;
        }

        if (array_key_exists('endereco', $data)) {
            $fields['endereco'] = $data['endereco'] ?? null;
        }
        if (array_key_exists('numero', $data)) {
            $fields['numero'] = $data['numero'] ?? null;
        }
        if (array_key_exists('complemento', $data)) {
            $fields['complemento'] = $data['complemento'] ?? null;
        }
        if (array_key_exists('bairro', $data)) {
            $fields['bairro'] = $data['bairro'] ?? null;
        }
        if (array_key_exists('cep', $data)) {
            $fields['cep'] = $data['cep'] ?? null;
        }
        if (array_key_exists('cidade', $data)) {
            $fields['cidade'] = $data['cidade'] ?? null;
        }
        if (array_key_exists('uf', $data)) {
            $fields['uf'] = $data['uf'] ?? null;
        }
        if (array_key_exists('telefone', $data)) {
            $telefone = $data['telefone'] ?? null;
            if ($telefone !== null && $telefone !== '') {
                $telefone = preg_replace('/\D/', '', (string)$telefone);
            }
            $fields['telefone'] = $telefone;
        }
        if (array_key_exists('email', $data)) {
            $fields['email'] = $data['email'] ?? null;
        }

        if (array_key_exists('cpf_cnpj', $data)) {
            $rawDocument = $data['cpf_cnpj'] ?? null;
            if ($rawDocument === null || $rawDocument === '') {
                $fields['cpf_cnpj'] = null;
            } else {
                $cpfCnpjDigits = preg_replace('/\D/', '', (string)$rawDocument);
                if (strlen($cpfCnpjDigits) !== 11 && strlen($cpfCnpjDigits) !== 14) {
                    http_response_code(400);
                    Response::json(['error' => 'CPF/CNPJ inválido']);
                    return;
                }

                if (strlen($cpfCnpjDigits) === 11 && !\Validator::validateCPF($cpfCnpjDigits)) {
                    http_response_code(400);
                    Response::json(['error' => 'CPF inválido']);
                    return;
                }

                if (strlen($cpfCnpjDigits) === 14 && !\Validator::validateCNPJ($cpfCnpjDigits)) {
                    http_response_code(400);
                    Response::json(['error' => 'CNPJ inválido']);
                    return;
                }

                if ($this->repo->hasCpfCnpj($cpfCnpjDigits, $id)) {
                    http_response_code(409);
                    Response::json([
                        'error' => strlen($cpfCnpjDigits) === 11 ? 'CPF já cadastrado.' : 'CNPJ já cadastrado.'
                    ]);
                    return;
                }

                $fields['cpf_cnpj'] = $cpfCnpjDigits;
            }
        }

        if (array_key_exists('status', $data)) {
            $fields['status'] = $data['status'] ? '1' : '0';
        }

        if (empty($fields)) {
            http_response_code(400);
            Response::json(['error' => 'Nenhum campo para atualizar']);
            return;
        }
        if (isset($fields['telefone']) && $fields['telefone'] !== null && $fields['telefone'] !== '' && !\Validator::validateTelefone($fields['telefone'])) {
            http_response_code(400);
            Response::json(['error' => 'Telefone inválido']);
            return;
        }

        if (isset($fields['email']) && $fields['email'] !== null && $fields['email'] !== '' && !\Validator::validateEmail($fields['email'])) {
            http_response_code(400);
            Response::json(['error' => 'Email inválido']);
            return;
        }

        $updated = $this->repo->update($id, $fields);
        if (!$updated) {
            http_response_code(404);
            Response::json(['error' => 'Cliente não encontrado']);
            return;
        }

        Response::json(['success' => true]);
    }

    public function delete(int $id): void
    {
        try {
            $this->repo->delete($id);
            Response::json(['message' => 'Cliente removido com sucesso']);
        } catch (\PDOException $e) {
            http_response_code(409);
            Response::json(['error' => 'Não foi possível excluir o cliente. Verifique vínculos com vendas.']);
        }
    }
}
