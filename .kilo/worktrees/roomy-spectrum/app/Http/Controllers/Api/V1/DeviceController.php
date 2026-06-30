<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Notification\DeviceService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    public function __construct(private readonly DeviceService $deviceService) {}

    /**
     * Register device for push notifications
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'fcm_token' => 'required|string',
            'platform' => 'required|in:ios,android,web',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        $this->deviceService->register($request->user(), $request->all());

        return ResponseService::success(null, 'Cihaz başarıyla kaydedildi');
    }

    /**
     * Unregister device
     */
    public function unregister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        $this->deviceService->unregister($request->user(), $request->input('device_id'));

        return ResponseService::success(null, 'Cihaz kaydı silindi');
    }
}
