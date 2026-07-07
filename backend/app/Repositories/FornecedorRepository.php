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

    private function hasColumn(string $col): bool
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

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM fornecedores WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
