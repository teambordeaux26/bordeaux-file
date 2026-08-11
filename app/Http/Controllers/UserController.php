<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public const EMAIL_DOMAIN = '@oas-dms.com';

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $query = User::query()->latest();

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $users = $query
            ->paginate(10)
            ->withQueryString()
            ->through(function ($u) {
                $emailLocal = str_ends_with(strtolower($u->email), static::EMAIL_DOMAIN)
                    ? substr($u->email, 0, -strlen(static::EMAIL_DOMAIN))
                    : explode('@', $u->email)[0];

                return [
                    'id'           => $u->id,
                    'name'         => $u->name,
                    'email'        => $u->email,
                    'email_local'  => $emailLocal,
                    'role'         => $u->role ?? 'employee',
                    'role_label'   => ucfirst($u->role ?? 'employee'),
                    'department'   => $u->department ?? '',
                    'position'     => $u->position ?? '',
                    'phone'        => $u->phone ?? '',
                    'status'       => $u->status ?? 'active',
                    'status_label' => ucfirst($u->status ?? 'active'),
                    'lastLogin'    => $u->last_login_at?->diffForHumans() ?? 'Never',
                ];
            });

        $departments = Department::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name]);

        return Inertia::render('Users/Index', [
            'users'         => $users,
            'departments'   => $departments,
            'emailDomain'   => static::EMAIL_DOMAIN,
            'roleOptions'   => ['admin', 'employee'],
            'statusOptions' => ['active', 'inactive'],
            'filters'       => [
                'q' => $filters['q'] ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email_local'  => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
            ],
            'role'         => ['required', Rule::in(['admin', 'employee'])],
            'department'   => 'nullable|string|max:120',
            'position'     => 'nullable|string|max:120',
            'phone'        => 'nullable|string|max:30',
            'status'       => ['required', Rule::in(['active', 'inactive'])],
            'password'     => 'required|string|min:8|confirmed',
        ], [
            'email_local.regex' => 'Email may only contain letters, numbers, dots, dashes, or underscores.',
        ]);

        $email = strtolower($data['email_local']) . static::EMAIL_DOMAIN;

        if (User::where('email', $email)->exists()) {
            return back()
                ->withErrors(['email_local' => 'This email address is already in use.'])
                ->withInput();
        }

        $user = User::create([
            'name'       => $data['name'],
            'email'      => $email,
            'password'   => Hash::make($data['password']),
            'role'       => $data['role'],
            'department' => $data['department'] ?: null,
            'position'   => $data['position'] ?: null,
            'phone'      => $data['phone'] ?: null,
            'status'     => $data['status'],
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'User Created',
            'description' => "Created user account for {$user->name} ({$user->email}) as {$user->role}.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', "User {$user->name} has been created.");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'email_local'  => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
            ],
            'role'         => ['required', Rule::in(['admin', 'employee'])],
            'department'   => 'nullable|string|max:120',
            'position'     => 'nullable|string|max:120',
            'phone'        => 'nullable|string|max:30',
            'status'       => ['required', Rule::in(['active', 'inactive'])],
            'password'     => 'nullable|string|min:8|confirmed',
        ], [
            'email_local.regex' => 'Email may only contain letters, numbers, dots, dashes, or underscores.',
        ]);

        // Prevent an admin from locking themselves out of the system.
        if ($user->id === Auth::id()) {
            if ($data['role'] !== 'admin') {
                return back()
                    ->withErrors(['role' => 'You cannot remove your own administrator role.'])
                    ->withInput();
            }
            if ($data['status'] !== 'active') {
                return back()
                    ->withErrors(['status' => 'You cannot deactivate your own account.'])
                    ->withInput();
            }
        }

        $email = strtolower($data['email_local']) . static::EMAIL_DOMAIN;

        if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return back()
                ->withErrors(['email_local' => 'This email address is already in use.'])
                ->withInput();
        }

        $user->fill([
            'name'       => $data['name'],
            'email'      => $email,
            'role'       => $data['role'],
            'department' => $data['department'] ?: null,
            'position'   => $data['position'] ?: null,
            'phone'      => $data['phone'] ?: null,
            'status'     => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'User Updated',
            'description' => "Updated user account for {$user->name} ({$user->email}).",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', "User {$user->name} has been updated.");
    }
}
