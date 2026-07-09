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
            Response::error('Payload inválido', 422, ['details' => $errors]);
            return;
        }

        if (empty($data['nome'])) {
            Response::error('Nome obrigatório', 400);
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
                Response::error('CPF/CNPJ inválido', 400);
                return;
            }

            if (strlen($cpfCnpjDigits) === 11 && !\Validator::validateCPF($cpfCnpjDigits)) {
                Response::error('CPF inválido', 400);
                return;
            }

            if (strlen($cpfCnpjDigits) === 14 && !\Validator::validateCNPJ($cpfCnpjDigits)) {
                Response::error('CNPJ inválido', 400);
                return;
            }

            if ($this->repo->hasCpfCnpj($cpfCnpjDigits)) {
                Response::error(
                    strlen($cpfCnpjDigits) === 11 ? 'CPF já cadastrado.' : 'CNPJ já cadastrado.',
                    409
                );
                return;
            }

            $data['cpf_cnpj'] = $cpfCnpjDigits;
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
            Response::error('Cliente não encontrado', 404);
            return;
        }

        $data = \App\Helpers\Request::body();
        $fields = [];

        if (array_key_exists('nome', $data)) {
            $nome = trim((string)($data['nome'] ?? ''));
            if ($nome === '') {
                Response::error('Nome obrigatório', 400);
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
                    Response::error('CPF/CNPJ inválido', 400);
                    return;
                }

                if (strlen($cpfCnpjDigits) === 11 && !\Validator::validateCPF($cpfCnpjDigits)) {
                    Response::error('CPF inválido', 400);
                    return;
                }

                if (strlen($cpfCnpjDigits) === 14 && !\Validator::validateCNPJ($cpfCnpjDigits)) {
                    Response::error('CNPJ inválido', 400);
                    return;
                }

                if ($this->repo->hasCpfCnpj($cpfCnpjDigits, $id)) {
                    Response::error(
                        strlen($cpfCnpjDigits) === 11 ? 'CPF já cadastrado.' : 'CNPJ já cadastrado.',
                        409
                    );
                    return;
                }

                $fields['cpf_cnpj'] = $cpfCnpjDigits;
            }
        }

        if (array_key_exists('status', $data)) {
            $fields['status'] = $data['status'] ? '1' : '0';
        }

        if (empty($fields)) {
            Response::error('Nenhum campo para atualizar', 400);
            return;
        }
        if (isset($fields['telefone']) && $fields['telefone'] !== null && $fields['telefone'] !== '' && !\Validator::validateTelefone($fields['telefone'])) {
            Response::error('Telefone inválido', 400);
            return;
        }

        if (isset($fields['email']) && $fields['email'] !== null && $fields['email'] !== '' && !\Validator::validateEmail($fields['email'])) {
            Response::error('Email inválido', 400);
            return;
        }

        $updated = $this->repo->update($id, $fields);
        if (!$updated) {
            Response::error('Cliente não encontrado', 404);
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
            Response::error('Não foi possível excluir o cliente. Verifique vínculos com vendas.', 409);
        }
    }
}
