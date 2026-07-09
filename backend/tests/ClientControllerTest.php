<?php

declare(strict_types=1);

use App\Controllers\ClientController;
use App\Repositories\ClientRepository;
use PHPUnit\Framework\TestCase;

final class ClientControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        unset($GLOBALS['SANITIZED_INPUT']);
        http_response_code(200);
    }

    public function testUpdateAllowsKeepingTheSameCpfCnpj(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE clientes (id INTEGER PRIMARY KEY AUTOINCREMENT, nome TEXT NOT NULL, endereco TEXT, numero TEXT, complemento TEXT, bairro TEXT, cep TEXT, cpf_cnpj TEXT, telefone TEXT, email TEXT, uf TEXT, status INTEGER, cidade TEXT, deleted_at TEXT)');

        $stmt = $pdo->prepare('INSERT INTO clientes (nome, cpf_cnpj, telefone, email, status) VALUES (:nome, :cpf_cnpj, :telefone, :email, :status)');
        $stmt->execute([
            'nome' => 'Cliente Original',
            'cpf_cnpj' => '11144477735',
            'telefone' => '11999999999',
            'email' => 'original@example.com',
            'status' => 1,
        ]);

        $GLOBALS['SANITIZED_INPUT'] = [
            'nome' => 'Cliente Atualizado',
            'cpf_cnpj' => '111.444.777-35',
            'telefone' => '(11) 99999-9999',
            'email' => 'atualizado@example.com',
            'status' => true,
        ];

        ob_start();
        (new ClientController($pdo, new ClientRepository($pdo)))->update(1);
        $raw = ob_get_clean();

        $this->assertSame(200, http_response_code());
        $payload = json_decode((string)$raw, true);
        $this->assertIsArray($payload);
        $this->assertTrue($payload['success'] ?? false);

        $stored = $pdo->query('SELECT nome, cpf_cnpj, telefone, email, status FROM clientes WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Cliente Atualizado', $stored['nome']);
        $this->assertSame('11144477735', $stored['cpf_cnpj']);
        $this->assertSame('11999999999', $stored['telefone']);
        $this->assertSame('atualizado@example.com', $stored['email']);
        $this->assertSame('1', $stored['status']);
    }
}
