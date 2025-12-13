<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = [];

    protected $session;
    protected $userId;
    protected $userRole;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->session  = \Config\Services::session();
        $this->userId   = $this->session->get('user_id') ?? null;
        $this->userRole = $this->session->get('role') ?? 'user';
    }

    protected function isAdmin(): bool
    {
        return $this->userRole === 'admin';
    }

    protected function getQueryUserId(): ?int
    {
        if ($this->isAdmin()) {
            $reqUser = service('request')->getGet('user_id');
            return $reqUser ?: null;
        }

        return $this->userId;
    }

    protected function filterByUserId($model)
    {
        $userId = $this->userId;
        if ($this->isAdmin()) {
            $reqUser = $this->request->getGet('user_id');
            if ($reqUser) $userId = $reqUser;
        }
        if ($userId) $model->where('user_id', $userId);
        return $model;
    }
}
