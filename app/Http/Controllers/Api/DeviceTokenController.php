<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreDeviceTokenRequest;
use App\Services\DeviceTokenService;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends ApiController
{
    public function __construct(
        protected DeviceTokenService $deviceTokenService
    ) {}

    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $token = $this->deviceTokenService->registerToken(
            $request->user(),
            $request->validated()
        );

        return $this->successResponse(
            [
                'id' => $token->id,
                'token' => $token->token,
                'platform' => $token->platform,
            ],
            'Token perangkat berhasil didaftarkan.',
            201
        );
    }
}
