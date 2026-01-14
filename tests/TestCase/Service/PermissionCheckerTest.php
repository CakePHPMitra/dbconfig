<?php
declare(strict_types=1);

namespace DbConfig\Test\TestCase\Service;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use DbConfig\Service\PermissionChecker;

/**
 * PermissionChecker Test Case
 */
class PermissionCheckerTest extends TestCase
{
    protected PermissionChecker $permissionChecker;

    /**
     * Setup method
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('DbConfig.permissions', []);
    }

    /**
     * Teardown method
     */
    public function tearDown(): void
    {
        Configure::delete('DbConfig.permissions');
        parent::tearDown();
    }

    /**
     * Test isAuthenticated returns false when no identity
     */
    public function testIsAuthenticatedWithNoIdentity(): void
    {
        $this->permissionChecker = new PermissionChecker();
        $request = new ServerRequest();

        $this->assertFalse($this->permissionChecker->isAuthenticated($request));
    }

    /**
     * Test isAuthenticated returns true when identity exists
     */
    public function testIsAuthenticatedWithIdentity(): void
    {
        $this->permissionChecker = new PermissionChecker();
        $request = (new ServerRequest())->withAttribute('identity', ['id' => 1, 'role' => 'admin']);

        $this->assertTrue($this->permissionChecker->isAuthenticated($request));
    }

    /**
     * Test getUserRole with array identity
     */
    public function testGetUserRoleWithArrayIdentity(): void
    {
        Configure::write('DbConfig.permissions', ['roleAttribute' => 'role']);
        $this->permissionChecker = new PermissionChecker();

        $identity = ['id' => 1, 'role' => 'admin'];
        $this->assertSame('admin', $this->permissionChecker->getUserRole($identity));
    }

    /**
     * Test getUserRole with nested attribute using dot notation
     */
    public function testGetUserRoleWithNestedAttribute(): void
    {
        Configure::write('DbConfig.permissions', ['roleAttribute' => 'user.role']);
        $this->permissionChecker = new PermissionChecker();

        $identity = ['id' => 1, 'user' => ['role' => 'editor']];
        $this->assertSame('editor', $this->permissionChecker->getUserRole($identity));
    }

    /**
     * Test getUserRole returns null for null identity
     */
    public function testGetUserRoleWithNullIdentity(): void
    {
        $this->permissionChecker = new PermissionChecker();
        $this->assertNull($this->permissionChecker->getUserRole(null));
    }

    /**
     * Test hasPermission returns false when not authenticated
     */
    public function testHasPermissionReturnsFalseWhenNotAuthenticated(): void
    {
        $this->permissionChecker = new PermissionChecker();
        $request = new ServerRequest();

        $this->assertFalse($this->permissionChecker->hasPermission($request, PermissionChecker::PERMISSION_VIEW));
    }

    /**
     * Test hasPermission with bypass roles
     */
    public function testHasPermissionWithBypassRoles(): void
    {
        Configure::write('DbConfig.permissions', [
            'roleAttribute' => 'role',
            'bypassRoles' => ['superadmin'],
        ]);
        $this->permissionChecker = new PermissionChecker();

        $request = (new ServerRequest())->withAttribute('identity', ['id' => 1, 'role' => 'superadmin']);

        $this->assertTrue($this->permissionChecker->hasPermission($request, PermissionChecker::PERMISSION_VIEW));
        $this->assertTrue($this->permissionChecker->hasPermission($request, PermissionChecker::PERMISSION_UPDATE));
    }

    /**
     * Test hasPermission with view roles
     */
    public function testHasPermissionWithViewRoles(): void
    {
        Configure::write('DbConfig.permissions', [
            'roleAttribute' => 'role',
            'viewRoles' => ['admin', 'manager'],
        ]);
        $this->permissionChecker = new PermissionChecker();

        $request = (new ServerRequest())->withAttribute('identity', ['id' => 1, 'role' => 'admin']);

        $this->assertTrue($this->permissionChecker->hasPermission($request, PermissionChecker::PERMISSION_VIEW));
        $this->assertFalse($this->permissionChecker->hasPermission($request, PermissionChecker::PERMISSION_UPDATE));
    }

    /**
     * Test hasPermission with update roles
     */
    public function testHasPermissionWithUpdateRoles(): void
    {
        Configure::write('DbConfig.permissions', [
            'roleAttribute' => 'role',
            'updateRoles' => ['admin'],
        ]);
        $this->permissionChecker = new PermissionChecker();

        $request = (new ServerRequest())->withAttribute('identity', ['id' => 1, 'role' => 'admin']);

        $this->assertTrue($this->permissionChecker->hasPermission($request, PermissionChecker::PERMISSION_UPDATE));
    }

    /**
     * Test hasPermission with wildcard allows all
     */
    public function testHasPermissionWithWildcard(): void
    {
        Configure::write('DbConfig.permissions', [
            'roleAttribute' => 'role',
            'viewRoles' => ['*'],
        ]);
        $this->permissionChecker = new PermissionChecker();

        $request = (new ServerRequest())->withAttribute('identity', ['id' => 1, 'role' => 'guest']);

        $this->assertTrue($this->permissionChecker->hasPermission($request, PermissionChecker::PERMISSION_VIEW));
    }

    /**
     * Test canView wrapper method
     */
    public function testCanView(): void
    {
        Configure::write('DbConfig.permissions', [
            'roleAttribute' => 'role',
            'viewRoles' => ['viewer'],
        ]);
        $this->permissionChecker = new PermissionChecker();

        $request = (new ServerRequest())->withAttribute('identity', ['id' => 1, 'role' => 'viewer']);

        $this->assertTrue($this->permissionChecker->canView($request));
    }

    /**
     * Test canUpdate wrapper method
     */
    public function testCanUpdate(): void
    {
        Configure::write('DbConfig.permissions', [
            'roleAttribute' => 'role',
            'updateRoles' => ['editor'],
        ]);
        $this->permissionChecker = new PermissionChecker();

        $request = (new ServerRequest())->withAttribute('identity', ['id' => 1, 'role' => 'editor']);

        $this->assertTrue($this->permissionChecker->canUpdate($request));
    }

    /**
     * Test getUnauthenticatedAction returns default
     */
    public function testGetUnauthenticatedActionDefault(): void
    {
        $this->permissionChecker = new PermissionChecker();

        $this->assertSame('redirect', $this->permissionChecker->getUnauthenticatedAction());
    }

    /**
     * Test getUnauthenticatedAction returns configured value
     */
    public function testGetUnauthenticatedActionConfigured(): void
    {
        Configure::write('DbConfig.permissions', [
            'unauthenticatedAction' => 'deny',
        ]);
        $this->permissionChecker = new PermissionChecker();

        $this->assertSame('deny', $this->permissionChecker->getUnauthenticatedAction());
    }

    /**
     * Test getLoginUrl returns default
     */
    public function testGetLoginUrlDefault(): void
    {
        $this->permissionChecker = new PermissionChecker();

        $this->assertSame(
            ['controller' => 'Users', 'action' => 'login'],
            $this->permissionChecker->getLoginUrl()
        );
    }

    /**
     * Test getLoginUrl returns configured value
     */
    public function testGetLoginUrlConfigured(): void
    {
        Configure::write('DbConfig.permissions', [
            'loginUrl' => '/auth/login',
        ]);
        $this->permissionChecker = new PermissionChecker();

        $this->assertSame('/auth/login', $this->permissionChecker->getLoginUrl());
    }

    /**
     * Test getIdentity with session resolver
     */
    public function testGetIdentityWithSessionResolver(): void
    {
        Configure::write('DbConfig.permissions', [
            'identityResolver' => 'session',
        ]);
        $this->permissionChecker = new PermissionChecker();

        $session = new \Cake\Http\Session();
        $session->write('Auth', ['id' => 1, 'role' => 'admin']);

        $request = (new ServerRequest())->withAttribute('session', $session);

        $identity = $this->permissionChecker->getIdentity($request);
        $this->assertSame(['id' => 1, 'role' => 'admin'], $identity);
    }

    /**
     * Test getIdentity with callable resolver
     */
    public function testGetIdentityWithCallableResolver(): void
    {
        Configure::write('DbConfig.permissions', [
            'identityResolver' => function ($request) {
                return ['id' => 99, 'role' => 'custom'];
            },
        ]);
        $this->permissionChecker = new PermissionChecker();

        $request = new ServerRequest();

        $identity = $this->permissionChecker->getIdentity($request);
        $this->assertSame(['id' => 99, 'role' => 'custom'], $identity);
    }

    /**
     * Test hasPermission with array of roles
     */
    public function testHasPermissionWithArrayOfRoles(): void
    {
        Configure::write('DbConfig.permissions', [
            'roleAttribute' => 'roles',
            'viewRoles' => ['editor'],
        ]);
        $this->permissionChecker = new PermissionChecker();

        $request = (new ServerRequest())->withAttribute('identity', [
            'id' => 1,
            'roles' => ['user', 'editor'],
        ]);

        $this->assertTrue($this->permissionChecker->hasPermission($request, PermissionChecker::PERMISSION_VIEW));
    }
}
