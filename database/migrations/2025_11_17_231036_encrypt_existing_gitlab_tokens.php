<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Encrypt any existing GitLab tokens that are stored in plain text
        $gitlabTokenSetting = DB::table('settings')
            ->where('key', 'gitlab_token')
            ->first();

        if ($gitlabTokenSetting && !empty($gitlabTokenSetting->value)) {
            try {
                // Try to decrypt - if it fails, it means the token is plain text
                decrypt($gitlabTokenSetting->value);
                // Token is already encrypted, do nothing
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Token is plain text, encrypt it
                DB::table('settings')
                    ->where('key', 'gitlab_token')
                    ->update(['value' => encrypt($gitlabTokenSetting->value)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Decrypt the GitLab token back to plain text
        $gitlabTokenSetting = DB::table('settings')
            ->where('key', 'gitlab_token')
            ->first();

        if ($gitlabTokenSetting && !empty($gitlabTokenSetting->value)) {
            try {
                // Try to decrypt - if successful, store as plain text
                $decrypted = decrypt($gitlabTokenSetting->value);
                DB::table('settings')
                    ->where('key', 'gitlab_token')
                    ->update(['value' => $decrypted]);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                // Token is already plain text, do nothing
            }
        }
    }
};
