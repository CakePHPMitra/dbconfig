<?php
declare(strict_types=1);

namespace DbConfig\Controller;

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\UnauthorizedException;
use DbConfig\Service\PermissionChecker;

/**
 * AppSettings Controller
 *
 * @property \DbConfig\Model\Table\AppSettingsTable $AppSettings
 */
class AppSettingsController extends AppController
{
    protected PermissionChecker $permissionChecker;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->permissionChecker = new PermissionChecker();
    }

    /**
     * Index method - displays and handles updates for app settings
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // Handle authentication/authorization
        $this->checkAccess();

        $query = $this->AppSettings->find()->where(['module' => 'App']);
        $appSettings = $this->paginate($query);
        $id = $this->request->getParam('id') ?? $this->request->getQuery('id') ?? null;

        // Check update permission
        $canUpdate = $this->permissionChecker->canUpdate($this->request);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if (!$canUpdate) {
                $this->Flash->error(__('You do not have permission to update settings.'));

                return $this->redirect(['action' => 'index']);
            }

            $appSetting = $this->AppSettings->get($id, contain: []);
            $data = $this->request->getData();

            // For encrypted settings, empty value means "keep existing"
            if (
                strtolower($appSetting->type) === 'encrypted'
                && (!isset($data['value']) || $data['value'] === '')
            ) {
                $this->Flash->info(__('No changes made. Value was empty.'));

                return $this->redirect(['action' => 'index']);
            }

            $appSetting = $this->AppSettings->patchEntity($appSetting, $data);

            if ($this->AppSettings->save($appSetting)) {
                $this->Flash->success(__('The app setting has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__('The app setting could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('appSettings', 'id', 'canUpdate'));
    }

    /**
     * Check access permissions - handles both authorization modes
     *
     * @return void
     * @throws \Cake\Http\Exception\ForbiddenException
     * @throws \Cake\Http\Exception\UnauthorizedException
     */
    protected function checkAccess(): void
    {
        // If Authorization plugin is loaded, let it handle authorization
        if ($this->permissionChecker->hasAuthorizationPlugin()) {
            $this->handleAuthorizationPluginMode();

            return;
        }

        // Simple role-based check mode
        $this->handleSimpleAuthMode();
    }

    /**
     * Handle authorization when Authorization plugin is available
     *
     * @return void
     */
    protected function handleAuthorizationPluginMode(): void
    {
        // Check if user is authenticated
        if (!$this->permissionChecker->isAuthenticated($this->request)) {
            $this->handleUnauthenticated();

            return;
        }

        // When Authorization plugin is loaded, check if component is available
        if ($this->components()->has('Authorization')) {
            // Create a dummy entity for policy check
            $setting = $this->AppSettings->newEmptyEntity();

            try {
                $this->Authorization->authorize($setting, 'index');
            } catch (\Authorization\Exception\ForbiddenException $e) {
                throw new ForbiddenException(__('You do not have permission to access settings.'));
            }
        } else {
            // Authorization plugin loaded but component not available
            // Fall back to simple check
            $this->handleSimpleAuthMode();
        }
    }

    /**
     * Handle simple authentication/role-based authorization
     *
     * @return void
     */
    protected function handleSimpleAuthMode(): void
    {
        // Check if user is authenticated
        if (!$this->permissionChecker->isAuthenticated($this->request)) {
            $this->handleUnauthenticated();

            return;
        }

        // Check view permission
        if (!$this->permissionChecker->canView($this->request)) {
            throw new ForbiddenException(__('You do not have permission to access settings.'));
        }
    }

    /**
     * Handle unauthenticated user based on configuration
     *
     * @return void
     * @throws \Cake\Http\Exception\UnauthorizedException
     */
    protected function handleUnauthenticated(): void
    {
        $action = $this->permissionChecker->getUnauthenticatedAction();

        switch ($action) {
            case 'redirect':
                $this->Flash->error(__('Please log in to access this page.'));
                $loginUrl = $this->permissionChecker->getLoginUrl();

                // Store response for redirect
                $response = $this->redirect($loginUrl);
                $this->setResponse($response);

                return;

            case 'allow':
                // Allow unauthenticated access (not recommended)
                return;

            case 'deny':
            default:
                throw new UnauthorizedException(__('Authentication required.'));
        }
    }
}
