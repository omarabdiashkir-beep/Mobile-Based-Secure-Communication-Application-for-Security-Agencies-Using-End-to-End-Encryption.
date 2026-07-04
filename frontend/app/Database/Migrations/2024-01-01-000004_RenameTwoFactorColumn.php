<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RenameTwoFactorColumn extends Migration
{
    public function up(): void
    {
        // Rename two_factor → 2FA
        $this->db->query("ALTER TABLE `users` CHANGE `two_factor` `2FA` TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE `users` CHANGE `2FA` `two_factor` TINYINT(1) NOT NULL DEFAULT 0");
    }
}
