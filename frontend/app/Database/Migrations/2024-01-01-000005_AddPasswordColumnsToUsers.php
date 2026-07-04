<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordColumnsToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [

            // 0 = never changed (force change on first login)
            // 1 = already changed at least once
            'password_changed' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => '2FA',
            ],

            // Date/time of last password change
            // Used to force change every 30 days
            'password_last_changed' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'default' => null,
                'after' => 'password_changed',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'password_changed');
        $this->forge->dropColumn('users', 'password_last_changed');
    }
}
