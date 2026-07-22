<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DocumentCategory;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::current();

        return Inertia::render('Settings/Index', [
            'settings' => [
                'hotline'                  => $settings->hotline,
                'office_hours'             => $settings->office_hours,
                'public_search_fields'     => $settings->public_search_fields ?? SystemSetting::defaultPublicSearchFields(),
                'public_search_categories' => $settings->public_search_categories ?? [],
                'employee_pages'           => $settings->employee_pages ?? SystemSetting::defaultEmployeePages(),
            ],
            'pageOptions'        => SystemSetting::employeePageOptions(),
            'publicFieldOptions' => SystemSetting::publicSearchFieldOptions(),
            'categories'         => DocumentCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request)
    {
        $pageKeys  = array_keys(SystemSetting::employeePageOptions());
        $fieldKeys = array_keys(SystemSetting::publicSearchFieldOptions());

        $validated = $request->validate([
            'hotline'                  => 'required|string|max:50',
            'office_hours'             => 'required|string|max:255',
            'public_search_fields'     => 'required|array|min:1',
            'public_search_fields.*'   => 'in:' . implode(',', $fieldKeys),
            'public_search_categories' => 'nullable|array',
            'public_search_categories.*' => 'integer|exists:document_categories,id',
            'employee_pages'           => 'required|array|min:1',
            'employee_pages.*'         => 'in:' . implode(',', $pageKeys),
        ]);

        $settings = SystemSetting::current();
        $settings->update([
            'hotline'                  => $validated['hotline'],
            'office_hours'             => $validated['office_hours'],
            'public_search_fields'     => $validated['public_search_fields'],
            'public_search_categories' => $validated['public_search_categories'] ?? [],
            'employee_pages'           => $validated['employee_pages'],
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Settings Updated',
            'description' => 'System settings were updated by ' . Auth::user()->name . '.',
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('settings.index')
            ->with('success', 'Settings saved successfully.');
    }
}
