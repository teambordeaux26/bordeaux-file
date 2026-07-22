<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get()->map(fn($u) => [
            'id'         => $u->id,
            'name'       => $u->name,
            'email'      => $u->email,
            'role'       => ucfirst($u->role ?? 'employee'),
            'department' => $u->department ?? '—',
            'status'     => ucfirst($u->status ?? 'active'),
            'lastLogin'  => $u->last_login_at?->diffForHumans() ?? 'Never',
        ]);

        return Inertia::render('Users/Index', compact('users'));
    }
}
