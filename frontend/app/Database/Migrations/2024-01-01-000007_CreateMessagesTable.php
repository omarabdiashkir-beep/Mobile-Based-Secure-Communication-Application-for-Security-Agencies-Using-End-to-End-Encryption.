<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMessagesTable extends Migration
{
    public function up(): void
    {
        // ── messages ─────────────────────────────
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'sender_id'     => ['type' => 'INT',    'unsigned' => true],
            'receiver_id'   => ['type' => 'INT',    'unsigned' => true],

            // text | image | video | voice | document
            'type'          => ['type' => 'ENUM',
                                'constraint' => ['text','image','video','voice','document'],
                                'default'    => 'text'],

            // text content (for text messages)
            'content'       => ['type' => 'TEXT', 'null' => true],

            // file fields (for media messages)
            'file_path'     => ['type' => 'VARCHAR', 'constraint' => 500,  'null' => true],
            'file_name'     => ['type' => 'VARCHAR', 'constraint' => 255,  'null' => true],
            'file_size'     => ['type' => 'BIGINT',  'unsigned' => true,   'null' => true],
            'file_mime'     => ['type' => 'VARCHAR', 'constraint' => 100,  'null' => true],
            'file_url'      => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],

            // reply / forward
            'reply_to_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],

            // flags
            'is_deleted'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'deleted_by'    => ['type' => 'INT',     'unsigned' => true, 'null' => true],

            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['sender_id', 'receiver_id']);
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('sender_id',   'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('receiver_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('messages');

        // ── message_status (delivery + read) ─────
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'message_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'      => ['type' => 'INT',    'unsigned' => true],
            // sent | delivered | read
            'status'       => ['type' => 'ENUM',
                                'constraint' => ['sent','delivered','read'],
                                'default'    => 'sent'],
            'delivered_at' => ['type' => 'DATETIME', 'null' => true],
            'read_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['message_id', 'user_id']);
        $this->forge->addForeignKey('message_id', 'messages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id',    'users',    'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('message_status');

        // ── message_reactions ─────────────────────
        $this->forge->addField([
            'id'         => ['type' => 'INT',    'unsigned' => true, 'auto_increment' => true],
            'message_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'    => ['type' => 'INT',    'unsigned' => true],
            'reaction'   => ['type' => 'VARCHAR','constraint' => 20], // emoji e.g. 👍❤️😂
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['message_id', 'user_id']);
        $this->forge->addForeignKey('message_id', 'messages', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id',    'users',    'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('message_reactions');
    }

    public function down(): void
    {
        $this->forge->dropTable('message_reactions', true);
        $this->forge->dropTable('message_status',    true);
        $this->forge->dropTable('messages',          true);
    }
}
