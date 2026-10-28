<?php

namespace App\Services;

use App\Models\AdminUser;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQrCodeUrl(AdminUser $admin, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            'AfricaQuizz',
            $admin->email,
            $secret
        );
    }

    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code, 2); // window 2 pour tolérer le décalage horaire
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(10));
        }
        return $codes;
    }
}