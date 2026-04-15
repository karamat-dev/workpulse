@push('head')
    @include('workpulse.partials.links')
    @include('workpulse.partials.styles')
@endpush

<x-fullscreen-layout>
    @include('workpulse.partials.markup')

    <script>
        (function () {
            async function boot() {
                try {
                    const res = await fetch('/api/bootstrap', {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                        },
                    });
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
                        DB.attendance = data.attendance || [];
                        DB.leaves = data.leaves || [];
                        DB.leaveBalances = data.leaveBalances || [];
                        DB.regulations = data.regulations || [];
                        DB.announcements = data.announcements || [];
                        DB.holidays = data.holidays || [];
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
                                manager: p.manager || DB.currentUser.manager,
                                phone: p.phone || DB.currentUser.phone,
                            });

                            const idx = (DB.employees || []).findIndex(e => e.id === DB.currentUser.id);
                            if (idx >= 0) {
                                DB.employees[idx] = Object.assign(DB.employees[idx], p, { id: p.employee_code || DB.employees[idx].id });
                            }
                        }
                    } catch (_) {
                    }

                    if (typeof initApp === 'function') {
                        initApp();
                    }
                } catch (error) {
                    console.error(error);

                    try {
                        showToast('Failed to load backend data. Please refresh.', 'red');
                    } catch (_) {
                    }
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
            } else {
                boot();
            }
        })();
    </script>

    @include('workpulse.partials.scripts')
</x-fullscreen-layout>
