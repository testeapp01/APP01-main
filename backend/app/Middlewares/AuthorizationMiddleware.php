<?php
namespace App\Middlewares;

/**
 * Role-Based Access Control middleware.
 *
 * Roles (least to most privileged):
 *   cliente  – read-only access to own data (e.g. delivery status)
 *   vendedor – create/view sales; cannot touch admin resources
 *   suporte  – view + create clients, suppliers, drivers; no destructive ops
 *   gerente  – full operational access; no user/company management
 *   admin    – full access including user management and destructive ops
 *
 * Usage in routes:
 *   AuthorizationMiddleware::requireRole(...AuthorizationMiddleware::MANAGERS);
 *   AuthorizationMiddleware::requireRole('admin');
 */
class AuthorizationMiddleware
{
    public const ROLE_ADMIN    = 'admin';
    // System-level admin (frontend may use 'adm sistema' label)
    public const ROLE_SYSTEM   = 'adm sistema';
    public const ROLE_GERENTE  = 'gerente';
    public const ROLE_SUPORTE  = 'suporte';
    public const ROLE_VENDEDOR = 'vendedor';
    public const ROLE_CLIENTE  = 'cliente';

    /** All authenticated staff roles (excludes 'cliente') */
    public const ALL_STAFF = [self::ROLE_ADMIN, self::ROLE_GERENTE, self::ROLE_SUPORTE, self::ROLE_VENDEDOR];

    /** Roles that can manage operational data (read/write but not delete) */
    public const MANAGERS = [self::ROLE_ADMIN, self::ROLE_GERENTE, self::ROLE_SUPORTE];

    /** Only admins and gerentes can create/edit catalog and financial data */
    public const SENIOR = [self::ROLE_ADMIN, self::ROLE_GERENTE];

    /** Only admin can perform destructive or user-management operations */
    public const ADMIN_ONLY = [self::ROLE_ADMIN, self::ROLE_SYSTEM];

    /**
     * Abort with 403 if the authenticated user's role is not in $allowedRoles.
     * Must be called AFTER AuthMiddleware::authenticate() has populated AUTH_USER.
     *
     * @param string ...$allowedRoles One or more allowed role strings.
     */
    public static function requireRole(string ...$allowedRoles): void
    {
        $authUser = $GLOBALS['AUTH_USER'] ?? null;
        if (!is_array($authUser)) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Não autenticado']);
            exit;
        }

        $role = strtolower(trim((string) ($authUser['role'] ?? '')));
        if (!in_array($role, $allowedRoles, true)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Acesso negado: permissão insuficiente']);
            exit;
        }
    }
}
