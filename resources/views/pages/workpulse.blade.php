@push('head')
    @include('workpulse.partials.links')
    @include('workpulse.partials.styles')
@endpush

<x-fullscreen-layout>
    @include('workpulse.partials.markup')

    <script>
        (function () {
            function showLoginScreen() {
                const loginScreen = document.getElementById('login-screen');
                if (loginScreen) {
                    loginScreen.style.display = 'flex';
                }

                const app = document.getElementById('app');
                if (app) {
                    app.classList.remove('visible');
                }

                if (typeof DB === 'object' && DB) {
                    DB.currentUser = null;
                    DB.currentRole = null;
                }
            }

            async function boot() {
                try {
                    const res = await fetch('/api/bootstrap', {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    if (res.status === 401 || res.status === 403) {
                        showLoginScreen();
                        return;
                    }

                    const contentType = res.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        showLoginScreen();
                        return;
                    }

                    const data = await res.json();

                    if (!data || !data.ok) {
                        throw new Error('bootstrap failed');
                    }

                    const loginScreen = document.getElementById('login-screen');
                    if (loginScreen) {
                        loginScreen.style.display = 'none';
                    }

                    const app = document.getElementById('app');
                    if (app) {
                        app.classList.add('visible');
                    }

                    if (typeof DB === 'object' && DB) {
                        DB.currentUser = data.currentUser;
                        DB.currentRole = data.currentRole;
                        DB.users = [data.currentUser];
                        DB.employees = data.employees || [];
                        DB.departments = data.departments || [];
                        DB.shifts = data.shifts || [];
                        DB.attendance = data.attendance || [];
                        DB.liveAttendance = data.liveAttendance || [];
                        DB.leaves = data.leaves || [];
                        DB.leaveTypes = data.leaveTypes || [];
                        DB.leavePolicies = data.leavePolicies || [];
                        DB.leaveBalances = data.leaveBalances || [];
                        DB.regulations = data.regulations || [];
                        DB.announcements = data.announcements || [];
                        DB.holidays = data.holidays || [];
                        DB.events = data.events || [];
                        DB.notifications = data.notifications || [];
                        DB.notificationCount = data.notificationCount || 0;
                    }

                    // Load full persisted profile for employee self-profile views
                    try {
                        const pr = await fetch('/api/me/profile', {
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json' },
                        });
                        const pd = await pr.json();
                        if (pd && pd.ok && pd.profile && typeof DB === 'object' && DB) {
                            const p = pd.profile;
                            const parts = String(p.name || '').trim().split(/\s+/);
                            const fname = parts[0] || DB.currentUser.fname;
                            const lname = parts.slice(1).join(' ');

                            DB.currentUser = Object.assign(DB.currentUser || {}, {
                                id: p.employee_code || DB.currentUser.id,
                                fname,
                                lname,
                                email: p.email || DB.currentUser.email,
                                dept: p.dept || DB.currentUser.dept,
                                desg: p.desg || DB.currentUser.desg,
                                doj: p.doj || DB.currentUser.doj,
                                dop: p.dop || DB.currentUser.dop,
                                lwd: p.lwd || DB.currentUser.lwd,
                                manager: p.manager || DB.currentUser.manager,
                                phone: p.phone || DB.currentUser.phone,
                            }, p);

                            const idx = (DB.employees || []).findIndex(e => e.id === DB.currentUser.id);
                            if (idx >= 0) {
                                DB.employees[idx] = Object.assign(DB.employees[idx], p, {
                                    id: p.employee_code || DB.employees[idx].id,
                                    fname,
                                    lname,
                                });
                            }
                        }
                    } catch (_) {
                    }

                    if (typeof initApp === 'function') {
                        initApp();
                    }
                } catch (error) {
                    console.error(error);
                    showLoginScreen();

                    try {
                        showToast('Please sign in to continue.', 'red');
                    } catch (_) {
                    }
                }
            }

            window.bootWorkpulse = boot;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>

    @include('workpulse.partials.scripts')
</x-fullscreen-layout>
