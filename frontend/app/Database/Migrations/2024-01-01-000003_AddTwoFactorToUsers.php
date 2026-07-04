<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTwoFactorToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'two_factor' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,      // 0 = disabled, 1 = enabled
                'after'      => 'status',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'two_factor');
    }
}
