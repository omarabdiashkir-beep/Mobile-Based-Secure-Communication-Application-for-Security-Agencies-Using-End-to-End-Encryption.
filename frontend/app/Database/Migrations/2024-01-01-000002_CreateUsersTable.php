<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            // ── Identity ─────────────────────────────
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'role_id'      => ['type' => 'INT', 'unsigned' => true, 'default' => 2], // 1=admin, 2=user

            // ── Login credentials ─────────────────────
            'email'        => ['type' => 'VARCHAR', 'constraint' => 191, 'unique' => true],
            'password'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'token'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],  // unlimited JWT

            // ── Profile ───────────────────────────────
            'name'         => ['type' => 'VARCHAR', 'constraint' => 150],
            'phone'        => ['type' => 'VARCHAR', 'constraint' => 30,  'null' => true],  // "tell"
            'address'      => ['type' => 'TEXT',                         'null' => true],
            'photo'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],  // file path / URL
            'occupation'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],

            // ── Account state ─────────────────────────
            'status'       => ['type' => 'ENUM', 'constraint' => ['active', 'inactive', 'suspended'],
                                'default' => 'active'],

            // ── Timestamps ────────────────────────────
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('users');
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
