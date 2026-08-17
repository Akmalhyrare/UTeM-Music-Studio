<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\RegisterRequest;
use App\Models\Staff;
use App\Models\Student;
use App\Rules\ValidEmailDomain;

class AuthController extends Controller
{
    // Show login page
    public function showLogin()
    {
        return view('auth.login');
    }

    /** Generic message shown for any failed login, so we never reveal whether an email exists. */
    private const INVALID_CREDENTIALS_MESSAGE = 'These credentials do not match our records.';

    /**
     * A syntactically valid (60-char) bcrypt hash with no matching plaintext,
     * used to blind response timing when no account matches. Must be a
     * well-formed bcrypt hash, otherwise Hash::check() throws a
     * RuntimeException ("This password does not use the Bcrypt algorithm")
     * because hashing.bcrypt.verify is enabled.
     */
    private const BLIND_TIMING_HASH = '$2y$12$aaaaaaaaaaaaaaaaaaaaaabbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    // Handle login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email', new ValidEmailDomain()],
            'password' => 'required',
        ]);

        try {
            // Only active accounts can log in (AUTH-1 fix)
            $staff = Staff::where('email', $request->email)->where('status', 'active')->first();
            if ($staff && Hash::check($request->password, $staff->password)) {
                Session::regenerate(); // prevent session fixation (CSRF-2 fix)
                Session::put('user_id',   $staff->staff_id);
                Session::put('user_name', $staff->full_name);
                Session::put('user_role', $staff->is_admin ? 'admin' : 'staff');
                Session::put('user_type', 'staff');
                Session::put('is_admin',  $staff->is_admin);
                $staff->forceFill(['last_login_at' => now()])->saveQuietly();
                return redirect()->route($staff->is_admin ? 'admin.dashboard' : 'staff.dashboard');
            }

            $student = Student::where('email', $request->email)->where('status', 'active')->first();
            if ($student && Hash::check($request->password, $student->password)) {
                Session::regenerate(); // prevent session fixation (CSRF-2 fix)
                Session::put('user_id',   $student->student_id);
                Session::put('user_name', $student->full_name);
                Session::put('user_role', 'student');
                Session::put('user_type', 'student');
                $student->forceFill(['last_login_at' => now()])->saveQuietly();
                return redirect()->route('student.dashboard');
            }

            // Run a dummy hash so response time does not reveal whether the email exists
            Hash::check($request->password, self::BLIND_TIMING_HASH);

            $this->logFailedAttempt($request);

            return back()->withErrors([
                'email' => self::INVALID_CREDENTIALS_MESSAGE,
            ])->onlyInput('email');
        } catch (QueryException $e) {
            Log::error('Login failed due to a database error.', [
                'email' => $request->email,
                'ip'    => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Unable to log in right now. Please try again later.',
            ])->onlyInput('email');
        } catch (\Throwable $e) {
            Log::error('Unexpected error during login.', [
                'email' => $request->email,
                'ip'    => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Something went wrong. Please try again later.',
            ])->onlyInput('email');
        }
    }

    // Log a failed login attempt without ever recording the password
    private function logFailedAttempt(Request $request): void
    {
        Log::warning('Failed login attempt.', [
            'email'     => $request->email,
            'ip'        => $request->ip(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    // Logout
    public function logout()
    {
        // Destroy the session entirely and rotate the CSRF token (CSRF-1 fix)
        Session::invalidate();
        Session::regenerateToken();
        return redirect()->route('login');
    }

    // Show register page
    public function showRegister()
    {
        return view('auth.register');
    }

    // Handle student registration
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        try {
            Student::create([
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'matric_no' => $data['matric_no'],
                'phone_no'  => $data['phone_no'] ?? null,
                'password'  => Hash::make($data['password']),
                'status'    => 'active',
            ]);
        } catch (QueryException $e) {
            // Never log the password; the DB error itself may also leak
            // values, so only log identifying (non-secret) fields.
            Log::error('Registration failed due to a database error.', [
                'email'     => $data['email'],
                'matric_no' => $data['matric_no'],
                'error'     => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['email' => 'Unable to create your account right now. Please try again later.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        return redirect()->route('login')
                         ->with('success', 'Account created! Please login.');
    }
}