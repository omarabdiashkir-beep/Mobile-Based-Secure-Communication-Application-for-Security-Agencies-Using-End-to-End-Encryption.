<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOnlineStatusAndContacts extends Migration
{
    public function up(): void
    {
        // ── Add online/last_seen columns to users ─
        $this->forge->addColumn('users', [
            'is_online' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'status',
            ],
            'last_seen' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'is_online',
            ],
        ]);

        // ── contacts table ────────────────────────
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'          => ['type' => 'INT', 'unsigned' => true],   // who added
            'contact_user_id'  => ['type' => 'INT', 'unsigned' => true],   // who was added
            'nickname'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true], // custom name
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'contact_user_id']);
        $this->forge->addForeignKey('user_id',         'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('contact_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('contacts');
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'is_online');
        $this->forge->dropColumn('users', 'last_seen');
        $this->forge->dropTable('contacts', true);
    }
}
