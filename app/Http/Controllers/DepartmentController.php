<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $query = Department::query()->orderBy('name');

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($q).'%'])
                    ->orWhereRaw('LOWER(code) LIKE ?', ['%'.mb_strtolower($q).'%']);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $departments = $query
            ->paginate(10)
            ->withQueryString()
            ->through(function (Department $d) {
                return [
                    'id'          => $d->id,
                    'name'        => $d->name,
                    'code'        => $d->code,
                    'description' => $d->description,
                    'status'      => $d->status,
                    'user_count'  => User::where('department', $d->name)->count(),
                    'created'     => optional($d->created_at)->format('M d, Y'),
                ];
            });

        return Inertia::render('Departments/Index', [
            'departments'   => $departments,
            'statusOptions' => ['active', 'inactive'],
            'filters'       => [
                'q' => $filters['q'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:120|unique:departments,name',
            'code'        => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $department = Department::create($data);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Department Created',
            'description' => "Created department '{$department->name}'.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('departments.index')
            ->with('success', "Department '{$department->name}' created.");
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120', Rule::unique('departments', 'name')->ignore($department->id)],
            'code'        => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'status'      => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $oldName = $department->name;
        $oldStatus = $department->status;

        $department->update($data);

        if ($oldName !== $department->name) {
            User::where('department', $oldName)->update(['department' => $department->name]);
        }

        $when = now()->format('F d, Y, \a\t g:i A');
        $actor = Auth::user()->name;
        $description = "Updated department '{$oldName}' to '{$department->name}'.";

        if ($oldStatus !== $department->status) {
            $verb = $department->status === 'active' ? 'activated' : 'deactivated';
            $description = "Department '{$department->name}' was {$verb} on {$when} by {$actor}.";
        }

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Department Updated',
            'description' => $description,
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('departments.index')
            ->with('success', "Department '{$department->name}' updated.");
    }
}
