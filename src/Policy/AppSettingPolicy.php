<?php
declare(strict_types=1);

namespace DbConfig\Policy;

use Cake\Core\Configure;
use Cake\Utility\Hash;
use DbConfig\Model\Entity\AppSetting;

/**
 * AppSetting Policy
 *
 * Used when cakephp/authorization plugin is loaded.
 * Still respects the DbConfig.permissions configuration.
 *
 * Note: The identity parameter uses mixed type to support both
 * Authorization\IdentityInterface and other identity implementations.
 */
class AppSettingPolicy
{
    protected array $config;

    public function __construct()
    {
        $this->config = Configure::read('DbConfig.permissions', []);
    }

    /**
     * Check if user can view/index settings
     *
     * @param mixed $user Identity object
     * @param \DbConfig\Model\Entity\AppSetting $setting
     * @return bool
     */
    public function canIndex(mixed $user, AppSetting $setting): bool
    {
        return $this->checkPermission($user, 'viewRoles');
    }

    /**
     * Alias for canIndex - used for view action
     *
     * @param mixed $user Identity object
     * @param \DbConfig\Model\Entity\AppSetting $setting
     * @return bool
     */
    public function canView(mixed $user, AppSetting $setting): bool
    {
        return $this->canIndex($user, $setting);
    }

    /**
     * Check if user can update/edit settings
     *
     * @param mixed $user Identity object
     * @param \DbConfig\Model\Entity\AppSetting $setting
     * @return bool
     */
    public function canEdit(mixed $user, AppSetting $setting): bool
    {
        return $this->checkPermission($user, 'updateRoles');
    }

    /**
     * Alias for canEdit - used for update action
     *
     * @param mixed $user Identity object
     * @param \DbConfig\Model\Entity\AppSetting $setting
     * @return bool
     */
    public function canUpdate(mixed $user, AppSetting $setting): bool
    {
        return $this->canEdit($user, $setting);
    }

    /**
     * Check permission based on configuration
     *
     * @param mixed $user Identity object
     * @param string $rolesKey Configuration key for allowed roles
     * @return bool
     */
    protected function checkPermission(mixed $user, string $rolesKey): bool
    {
        $role = $this->getUserRole($user);

        if ($role === null) {
            return false;
        }

        // Check bypass roles first
        $bypassRoles = $this->config['bypassRoles'] ?? [];
        if ($this->roleMatches($role, $bypassRoles)) {
            return true;
        }

        $allowedRoles = $this->config[$rolesKey] ?? [];

        // Handle wildcard
        if (in_array('*', $allowedRoles, true)) {
            return true;
        }

        return $this->roleMatches($role, $allowedRoles);
    }

    /**
     * Extract role from identity
     *
     * @param mixed $user Identity object
     * @return mixed
     */
    protected function getUserRole(mixed $user): mixed
    {
        if ($user === null) {
            return null;
        }

        $roleAttribute = $this->config['roleAttribute'] ?? 'role';

        // Try getOriginalData() for Authorization Identity
        if (method_exists($user, 'getOriginalData')) {
            $originalData = $user->getOriginalData();

            if (is_array($originalData)) {
                return Hash::get($originalData, $roleAttribute);
            }

            if (is_object($originalData) && method_exists($originalData, 'toArray')) {
                return Hash::get($originalData->toArray(), $roleAttribute);
            }
        }

        // Try get() method
        if (method_exists($user, 'get')) {
            $role = $user->get($roleAttribute);
            if ($role !== null) {
                return $role;
            }
        }

        // Try array access
        if ($user instanceof \ArrayAccess) {
            try {
                $data = method_exists($user, 'toArray') ? $user->toArray() : (array)$user;

                return Hash::get($data, $roleAttribute);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
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
        if (is_string($role)) {
            return in_array($role, $allowedRoles, true);
        }

        if (is_array($role)) {
            return !empty(array_intersect($role, $allowedRoles));
        }

        return false;
    }
}
