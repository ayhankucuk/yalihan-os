<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Mobile\ProfileService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profileService) {}

    public function show(Request $request)
    {
        return ResponseService::success($request->user(), 'Kullanıcı bilgileri getirildi');
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        $user = $this->profileService->updateProfile($request->user(), $request->all());

        return ResponseService::success($user, 'Profil güncellendi');
    }

    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        $user = $this->profileService->updatePhoto($request->user(), $request->file('photo'));

        return ResponseService::success(['profile_photo_url' => $user->profile_photo_url], 'Profil fotoğrafı güncellendi');
    }
}
