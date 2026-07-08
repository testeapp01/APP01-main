<?php
namespace App\Repositories;

use PDO;

class FornecedorRepository
{
    private ?array $colsCache = null;

    public function __construct(private PDO $pdo)
    {
    }

    private function columns(): array
    {
        if ($this->colsCache !== null) return $this->colsCache;
        try {
            $stmt = $this->pdo->query('SHOW COLUMNS FROM fornecedores');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->colsCache = array_values(array_filter(array_map(static fn(array $r) => $r['Field'] ?? null, $rows)));
        } catch (\Throwable) {
            $this->colsCache = [];
        }
        return $this->colsCache;
    }

    public function hasColumn(string $col): bool
    {
        return in_array($col, $this->columns(), true);
    }

    public function allSelectable(array $select): array
    {
        $cols = implode(', ', $select);
        $stmt = $this->pdo->query('SELECT ' . $cols . ' FROM fornecedores ORDER BY razao_social ASC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function hasCnpj(string $cnpjDigits): bool
    {
        if (!$this->hasColumn('cnpj')) return false;
        $stmt = $this->pdo->query('SELECT cnpj FROM fornecedores WHERE cnpj IS NOT NULL AND cnpj <> ""');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $current = preg_replace('/\D/', '', (string)($r['cnpj'] ?? ''));
            if ($current !== '' && $current === $cnpjDigits) return true;
        }
        return false;
    }

    public function create(array $data): int
    {
        $possible = [
            'razao_social' => $data['razao_social'] ?? null,
            'endereco' => $data['endereco'] ?? null,
            'numero' => $data['numero'] ?? null,
            'complemento' => $data['complemento'] ?? null,
            'bairro' => $data['bairro'] ?? null,
            'cep' => $data['cep'] ?? null,
            'cidade' => $data['cidade'] ?? null,
            'cnpj' => $data['cnpj'] ?? null,
            'email' => $data['email'] ?? null,
            'telefone' => $data['telefone'] ?? null,
            'status' => $data['status'] ?? 1,
            'uf' => $data['uf'] ?? null,
        ];

        $insertData = array_filter(
            $possible,
            fn($value, $column) => $this->hasColumn((string)$column),
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($insertData)) {
            throw new \RuntimeException('Tabela fornecedores sem colunas compatíveis para inserção.');
        }

        $columns = array_keys($insertData);
        $placeholders = array_map(static fn(string $column) => ':' . $column, $columns);
        $stmt = $this->pdo->prepare('INSERT INTO fornecedores (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
        $stmt->execute($insertData);
        return (int)$this->pdo->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM fornecedores WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
