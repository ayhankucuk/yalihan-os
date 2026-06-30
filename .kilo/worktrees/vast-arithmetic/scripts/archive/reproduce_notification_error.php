<?php

use App\Http\Controllers\Admin\NotificationController;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

try {
    // Login as admin
    $user = User::where('email', 'ayhankucuk@gmail.com')->first();
    if (!$user) {
        throw new Exception("Admin user not found");
    }
    Auth::login($user);

    echo "User logged in: " . $user->id . "\n";

    $controller = new NotificationController();
    $response = $controller->unread();

    echo "Response Status: " . $response->getStatusCode() . "\n";
    print_r($response->getData());

} catch (\Exception $e) {
    echo "Caught Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
