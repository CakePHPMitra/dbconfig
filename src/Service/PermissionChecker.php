<?php
declare(strict_types=1);

namespace DbConfig\Service;

use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Utility\Hash;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Permission Checker Service
 *
 * Handles permission checking for the DbConfig plugin.
 * Works in two modes:
 * - With cakephp/authorization: Uses Policy classes
 * - Without: Uses simple role-based configuration checks
 */
class PermissionChecker
{
    public const PERMISSION_VIEW = 'view';
    public const PERMISSION_UPDATE = 'update';

    protected array $config;

    public function __construct()
    {
        $this->config = Configure::read('DbConfig.permissions', []);
    }

    /**
     * Check if the Authorization plugin is available
     *
     * @return bool
     */
    public function hasAuthorizationPlugin(): bool
    {
        return Plugin::isLoaded('Authorization');
    }

    /**
     * Get identity from request based on configuration
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return mixed
     */
    public function getIdentity(ServerRequestInterface $request): mixed
    {
        $resolver = $this->config['identityResolver'] ?? 'attribute';

        if (is_callable($resolver)) {
            return $resolver($request);
        }

        return match ($resolver) {
            'session' => $request->getAttribute('session')?->read('Auth'),
            default => $request->getAttribute('identity'),
        };
    }

    /**
     * Check if user is authenticated
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     */
    public function isAuthenticated(ServerRequestInterface $request): bool
    {
        $identity = $this->getIdentity($request);

        return $identity !== null;
    }

    /**
     * Get user's role from identity
     *
     * @param mixed $identity
     * @return mixed
     */
    public function getUserRole(mixed $identity): mixed
    {
        if ($identity === null) {
            return null;
        }

        $roleAttribute = $this->config['roleAttribute'] ?? 'role';

        // Support object with getter or array access
        if (is_object($identity)) {
            // Try get() method (for Identity objects)
            if (method_exists($identity, 'get')) {
                $role = $identity->get($roleAttribute);
                if ($role !== null) {
                    return $role;
                }
            }

            // Try getter method (e.g., getRole())
            $getter = 'get' . str_replace(' ', '', ucwords(str_replace(['.', '_'], ' ', $roleAttribute)));
            if (method_exists($identity, $getter)) {
                return $identity->$getter();
            }

            // Try direct property access
            $simpleAttr = explode('.', $roleAttribute)[0];
            if (isset($identity->$simpleAttr)) {
                $value = $identity->$simpleAttr;
                // Handle dot notation for nested
                if (str_contains($roleAttribute, '.') && is_array($value)) {
                    return Hash::get([$simpleAttr => $value], $roleAttribute);
                }

                return $value;
            }

            // Try array access (for entities)
            if ($identity instanceof \ArrayAccess) {
                try {
                    $data = method_exists($identity, 'toArray') ? $identity->toArray() : (array)$identity;

                    return Hash::get($data, $roleAttribute);
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        if (is_array($identity)) {
            return Hash::get($identity, $roleAttribute);
        }

        return null;
    }

    /**
     * Check if user has specific permission (simple role check mode)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param string $permission
     * @return bool
     */
    public function hasPermission(ServerRequestInterface $request, string $permission): bool
    {
        $identity = $this->getIdentity($request);

        if ($identity === null) {
            return false;
        }

        $role = $this->getUserRole($identity);

        if ($role === null) {
            return false;
        }

        // Check bypass roles first
        $bypassRoles = $this->config['bypassRoles'] ?? [];
        if ($this->roleMatches($role, $bypassRoles)) {
            return true;
        }

        // Check specific permission
        $allowedRoles = match ($permission) {
            self::PERMISSION_VIEW => $this->config['viewRoles'] ?? [],
            self::PERMISSION_UPDATE => $this->config['updateRoles'] ?? [],
            default => [],
        };

        // Handle wildcard
        if (in_array('*', $allowedRoles, true)) {
            return true;
        }

        return $this->roleMatches($role, $allowedRoles);
    }

    /**
     * Check if role matches any in the allowed list
     *
     * @param mixed $role
     * @param array $allowedRoles
     * @return bool
     */
    protected function roleMatches(mixed $role, array $allowedRoles): bool
    {
        // Handle role as string
        if (is_string($role)) {
            return in_array($role, $allowedRoles, true);
        }

        // Handle role as array of roles
        if (is_array($role)) {
            return !empty(array_intersect($role, $allowedRoles));
        }

        return false;
    }

    /**
     * Get configured action for unauthenticated users
     *
     * @return string
     */
    public function getUnauthenticatedAction(): string
    {
        return $this->config['unauthenticatedAction'] ?? 'redirect';
    }

    /**
     * Get configured login URL
     *
     * @return array|string
     */
    public function getLoginUrl(): array|string
    {
        return $this->config['loginUrl'] ?? ['controller' => 'Users', 'action' => 'login'];
    }

    /**
     * Check if user can view settings (for template use)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     */
    public function canView(ServerRequestInterface $request): bool
    {
        return $this->hasPermission($request, self::PERMISSION_VIEW);
    }

    /**
     * Check if user can update settings (for template use)
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     */
    public function canUpdate(ServerRequestInterface $request): bool
    {
        return $this->hasPermission($request, self::PERMISSION_UPDATE);
    }
}
