<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMessageDeletionsTable extends Migration
{
    public function up(): void
    {
        // Tracks per-user "delete for me" — message stays on server,
        // just hidden from the deleting user's conversation view.
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'message_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'    => ['type' => 'INT',    'unsigned' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['message_id', 'user_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('message_id', 'messages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id',    'users',    'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('message_deletions');
    }

    public function down(): void
    {
        $this->forge->dropTable('message_deletions', true);
    }
}
