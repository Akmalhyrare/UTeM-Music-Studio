<div id="tab-security" class="settings-tab-content" style="display:none;">

    <div class="card border-0 shadow-sm mb-3" style="max-width:600px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3">🔒 Change Password</h6>
            <form method="POST" action="{{ route($routePrefix . '.password') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">Current Password <span class="text-danger">*</span></label>
                    <input type="password"
                           name="current_password"
                           class="form-control"
                           style="font-size:13px;"
                           required>
                </div>

                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;">New Password <span class="text-danger">*</span></label>
                    <input type="password"
                           name="password"
                           class="form-control"
                           style="font-size:13px;"
                           required>
                    <small class="text-muted" style="font-size:12px;">
                        Minimum 8 characters, including an uppercase letter, a lowercase letter, a number and a special character.
                    </small>
                </div>

                <div class="mb-4">
                    <label class="form-label" style="font-size:13px;">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password"
                           name="password_confirmation"
                           class="form-control"
                           style="font-size:13px;"
                           required>
                </div>

                <button type="submit"
                        class="btn"
                        style="background:#7C3AED;color:#fff;font-size:13px;border-radius:8px;">
                    🔒 Update Password
                </button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3" style="max-width:600px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3">🕑 Account Activity</h6>
            <p style="font-size:13px;" class="mb-1">
                <strong>Last login:</strong>
                {{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never' }}
            </p>
            <p style="font-size:13px;" class="mb-0">
                <strong>Password last changed:</strong>
                {{ $user->password_changed_at ? $user->password_changed_at->format('d M Y, h:i A') : 'Never' }}
            </p>
        </div>
    </div>

    {{-- Session Management — hidden from UI for all roles per requirements.
         Backend route/controller (SettingsController::logoutOtherSessions)
         intentionally left intact; only this card's rendering is disabled. --}}
    @if(false)
    <div class="card border-0 shadow-sm" style="max-width:600px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3">💻 Session Management</h6>
            <p style="font-size:13px;" class="mb-3">
                @if ($otherSessionsCount > 0)
                    You are currently logged in on {{ $otherSessionsCount }} other device(s)/browser(s).
                @else
                    You're only logged in on this device.
                @endif
            </p>
            <form method="POST"
                  action="{{ route($routePrefix . '.logout-others') }}"
                  onsubmit="return confirm('Log out from all other devices and browsers?');">
                @csrf
                <button type="submit"
                        class="btn btn-outline-danger"
                        style="font-size:13px;border-radius:8px;"
                        {{ $otherSessionsCount > 0 ? '' : 'disabled' }}>
                    🚪 Logout from other devices
                </button>
            </form>
        </div>
    </div>
    @endif

</div>
