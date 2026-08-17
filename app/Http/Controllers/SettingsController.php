<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAccountPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    // Show the account settings page (Profile + Security tabs)
    public function index()
    {
        $isStaff = session('user_type') === 'staff';
        $user = $this->currentUser();
        $routePrefix = $isStaff ? 'staff.settings' : 'student.settings';
        $view = $isStaff ? 'staff.settings.index' : 'student.settings.index';

        return view($view, [
            'user' => $user,
            'routePrefix' => $routePrefix,
            'otherSessionsCount' => $this->countOtherSessions(),
        ]);
    }

    // Update name, email and phone number
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $this->currentUser();
        $user->update($request->validated());

        Session::put('user_name', $user->full_name);

        return back()->with('success', 'Profile updated successfully.');
    }

    // Change password after verifying the current password
    public function updatePassword(UpdateAccountPasswordRequest $request)
    {
        $user = $this->currentUser();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ])->withInput();
        }

        if (Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'The new password must be different from your current password.',
            ])->withInput();
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    // Log out every other active session belonging to this account
    public function logoutOtherSessions(Request $request)
    {
        $currentId = Session::getId();
        $userId = session('user_id');
        $userType = session('user_type');
        $deleted = 0;

        DB::table('sessions')
            ->where('id', '!=', $currentId)
            ->get()
            ->each(function ($row) use ($userId, $userType, &$deleted) {
                $payload = $this->decodeSessionPayload($row->payload);

                if ($payload && ($payload['user_id'] ?? null) == $userId && ($payload['user_type'] ?? null) === $userType) {
                    DB::table('sessions')->where('id', $row->id)->delete();
                    $deleted++;
                }
            });

        $message = $deleted > 0
            ? "Logged out from {$deleted} other device(s)."
            : 'No other active sessions were found.';

        return back()->with('success', $message);
    }

    private function currentUser(): Staff|Student
    {
        return session('user_type') === 'staff'
            ? Staff::findOrFail(session('user_id'))
            : Student::findOrFail(session('user_id'));
    }

    private function countOtherSessions(): int
    {
        $currentId = Session::getId();
        $userId = session('user_id');
        $userType = session('user_type');

        return DB::table('sessions')
            ->where('id', '!=', $currentId)
            ->get()
            ->filter(function ($row) use ($userId, $userType) {
                $payload = $this->decodeSessionPayload($row->payload);

                return $payload && ($payload['user_id'] ?? null) == $userId && ($payload['user_type'] ?? null) === $userType;
            })
            ->count();
    }

    private function decodeSessionPayload(string $payload): ?array
    {
        $data = @unserialize(base64_decode($payload));

        return is_array($data) ? $data : null;
    }
}
