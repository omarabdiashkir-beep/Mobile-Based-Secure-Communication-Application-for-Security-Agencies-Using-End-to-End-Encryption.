<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBlockedUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true],  // who blocked
            'blocked_user_id'=> ['type' => 'INT', 'unsigned' => true],  // who got blocked
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'blocked_user_id']);
        $this->forge->addForeignKey('user_id',         'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('blocked_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('blocked_users');
    }

    public function down(): void
    {
        $this->forge->dropTable('blocked_users', true);
    }
}
