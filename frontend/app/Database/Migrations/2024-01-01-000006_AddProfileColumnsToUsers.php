<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileColumnsToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'name',
            ],
            'bio' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'username',
            ],
        ]);

        // unique index on username
        $this->db->query('ALTER TABLE `users` ADD UNIQUE INDEX `users_username_unique` (`username`)');
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'username');
        $this->forge->dropColumn('users', 'bio');
    }
}
