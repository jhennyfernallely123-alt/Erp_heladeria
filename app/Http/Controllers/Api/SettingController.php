<?php

namespace App\Http\Controllers\Api;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SettingController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $settings = BusinessSetting::pluck('value', 'key');
        return $this->successResponse($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($validated['settings'] as $key => $val) {
            BusinessSetting::set((string) $key, (string) $val);
        }

        return $this->successResponse(BusinessSetting::pluck('value', 'key'), 'Configuraciones actualizadas exitosamente');
    }
}
