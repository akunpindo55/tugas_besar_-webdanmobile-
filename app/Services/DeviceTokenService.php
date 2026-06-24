<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;

class DeviceTokenService
{
    public function registerToken(User $user, array $data): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            [
                'token' => $data['token'],
            ],
            [
                'user_id' => $user->id,
                'platform' => $data['platform'],
            ]
        );
    }
}
