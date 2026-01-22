<?php
declare(strict_types=1);

namespace DbConfig\Controller;

use Cake\Controller\Controller;

class AppController extends Controller
{
    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
    }
}
