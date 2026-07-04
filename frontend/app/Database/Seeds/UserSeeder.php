<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds two users:
 *   admin@app.com  / password: Admin@1234   (role: admin)
 *   user@app.com   / password: User@1234    (role: user)
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->db->table('users')->insertBatch([
            [
                'role_id'    => 1, // admin
                'name'       => 'System Admin',
                'email'      => 'admin@app.com',
                'password'   => password_hash('Admin@1234', PASSWORD_ARGON2ID),
                'phone'      => '+1-555-0001',
                'address'    => '123 Server Lane, Cloud City',
                'occupation' => 'System Administrator',
                'photo'      => null,
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'role_id'    => 2, // user
                'name'       => 'John Doe',
                'email'      => 'user@app.com',
                'password'   => password_hash('User@1234', PASSWORD_ARGON2ID),
                'phone'      => '+1-555-0002',
                'address'    => '456 Mobile Ave, App Town',
                'occupation' => 'Software Engineer',
                'photo'      => null,
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
}
