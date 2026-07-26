<?php

namespace App\Concerns;

use Illuminate\Contracts\Encryption\DecryptException;
use Modules\Core\Setting;

trait ResolvesServiceTokens
{
    /**
     * Resolve the encrypted GitHub PAT from the settings table.
     *
     * @throws \Exception
     */
    protected function resolveGitHubToken(): string
    {
        $encryptedToken = Setting::query()->where('key', 'github_token')->value('value');

        try {
            $token = $encryptedToken ? decrypt($encryptedToken) : null;
        } catch (DecryptException $e) {
            $token = null;
        }

        if (empty($token)) {
            throw new \Exception('GitHub token not configured or could not be decrypted');
        }

        return $token;
    }
}
