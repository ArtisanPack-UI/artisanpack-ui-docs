<?php

namespace App\Concerns;

use Modules\Core\Setting;

trait ResolvesServiceTokens
{
    /**
     * Resolve the API token from encrypted settings based on the detected source
     *
     * @throws \Exception
     */
    protected function resolveToken(string $source): string
    {
        $settingKey = "{$source}_token";
        $brandName = match ($source) {
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            default => ucfirst($source),
        };

        $encryptedToken = Setting::where('key', $settingKey)->first()?->value;

        try {
            $token = $encryptedToken ? decrypt($encryptedToken) : null;
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $token = null;
        }

        if (empty($token)) {
            throw new \Exception("{$brandName} token not configured or could not be decrypted");
        }

        return $token;
    }
}
