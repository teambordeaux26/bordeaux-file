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
        ]);

        $query = Department::query()->orderBy('name');

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
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

    public function destroy(Request $request, Department $department)
    {
        $memberCount = User::where('department', $department->name)->count();

        if ($memberCount > 0) {
            return redirect()->route('departments.index')->with(
                'error',
                "Cannot delete '{$department->name}' — {$memberCount} user(s) are still assigned to it. Reassign them first."
            );
        }

        $name = $department->name;
        $department->delete();

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Department Deleted',
            'description' => "Deleted department '{$name}'.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('departments.index')
            ->with('success', "Department '{$name}' deleted.");
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

        $department->update($data);

        if ($oldName !== $department->name) {
            User::where('department', $oldName)->update(['department' => $department->name]);
        }

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Department Updated',
            'description' => "Updated department '{$oldName}' → '{$department->name}'.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('departments.index')
            ->with('success', "Department '{$department->name}' updated.");
    }
}
