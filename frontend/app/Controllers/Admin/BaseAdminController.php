<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;

class BaseAdminController extends Controller
{
    protected function checkAuth(): bool
    {
        return (bool) session()->get('admin_logged_in');
    }

    protected function requireAuth(): ?\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAuth()) {
            return redirect()->to(base_url('admin/login'));
        }
        return null;
    }

    protected function db()
    {
        return \Config\Database::connect();
    }
}
