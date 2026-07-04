<?php

namespace App\Controllers\Admin;

class AdminAuthController extends BaseAdminController
{
    private string $adminUser = 'admin';
    private string $adminPass = 'Admin@123';

    public function login()
    {
        if (session()->get('admin_logged_in')) {
            return redirect()->to(base_url('admin'));
        }

        // Use $_POST directly — avoids any CI4 method-detection quirks
        if (!empty($_POST['username'])) {
            $username = trim($_POST['username']);
            $password = $_POST['password'] ?? '';

            if ($username === $this->adminUser && $password === $this->adminPass) {
                session()->set('admin_logged_in', true);
                session()->set('admin_username', $username);

                while (ob_get_level()) ob_end_clean();
                header('Location: ' . base_url('admin'));
                exit;
            }

            return view('admin/login', ['error' => 'Invalid username or password.']);
        }

        return view('admin/login', ['error' => null]);
    }

    public function logout()
    {
        session()->remove(['admin_logged_in', 'admin_username']);
        header('Location: ' . base_url('admin/login'));
        exit;
    }
}
