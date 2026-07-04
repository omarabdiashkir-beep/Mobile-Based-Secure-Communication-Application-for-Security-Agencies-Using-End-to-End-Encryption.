<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 50],   // Admin, User
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 50],   // admin, user
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('roles');

        // Seed the two roles right away
        $this->db->table('roles')->insertBatch([
            ['name' => 'Admin', 'slug' => 'admin', 'created_at' => date('Y-m-d H:i:s')],
            ['name' => 'User',  'slug' => 'user',  'created_at' => date('Y-m-d H:i:s')],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('roles', true);
    }
}
