<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePublicKeysAndEncryptMessages extends Migration
{
    public function up(): void
    {
        // ── public_keys table ─────────────────────────────────
        // Each user uploads their RSA public key from their device.
        // Private key NEVER leaves the device.
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],

            // RSA-4096 public key (PEM format)
            'public_key'  => ['type' => 'TEXT'],

            // SHA-256 fingerprint of the public key (for verification)
            'fingerprint' => ['type' => 'VARCHAR', 'constraint' => 64],

            // device that owns this key
            'device_id'   => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'device_name' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],

            'is_active'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'is_active']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('public_keys');

        // ── Add E2EE columns to messages ──────────────────────
        //
        // Flow:
        //  client generates random AES-256 key
        //  client encrypts message  → stores in:  content (base64 ciphertext)
        //  client wraps AES key with receiver RSA pubkey → encrypted_key
        //  client sends:  content, encrypted_key, iv, tag
        //  server stores all four — cannot read plaintext
        //  receiver decrypts encrypted_key with private key → gets AES key
        //  receiver decrypts content with AES key
        //
        $this->forge->addColumn('messages', [
            // base64-encoded AES-256-GCM ciphertext (replaces plaintext content)
            'is_encrypted'  => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'content',
            ],
            // AES key encrypted with receiver RSA public key (base64)
            'encrypted_key' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'is_encrypted',
            ],
            // AES-GCM IV (base64, 12 bytes)
            'iv'            => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'encrypted_key',
            ],
            // AES-GCM authentication tag (base64, 16 bytes)
            'tag'           => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'after'      => 'iv',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('messages', 'is_encrypted');
        $this->forge->dropColumn('messages', 'encrypted_key');
        $this->forge->dropColumn('messages', 'iv');
        $this->forge->dropColumn('messages', 'tag');
        $this->forge->dropTable('public_keys', true);
    }
}
