<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DocumentCategory;
use App\Models\RequestType;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DocumentRetentionService;
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
                'retention_periods'        => DocumentRetentionService::persistable(
                    $settings->retention_periods ?? []
                ),
                'official_signer_user_id'  => $settings->official_signer_user_id,
            ],
            'signers' => User::query()
                ->where('status', 'active')
                ->whereIn('role', ['admin', 'employee'])
                ->orderBy('name')
                ->get(['id', 'name', 'position', 'signature_path'])
                ->map(fn (User $user) => [
                    'id'            => $user->id,
                    'name'          => $user->name,
                    'position'      => $user->position ?: 'Staff',
                    'has_signature' => filled($user->signature_path),
                ]),
            'requestTypes'       => RequestType::settingsPayload(),
            'pageOptions'        => SystemSetting::employeePageOptions(),
            'publicFieldOptions' => SystemSetting::publicSearchFieldOptions(),
            'categories'         => DocumentCategory::query()
                ->with('parent')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn ($cat) => [
                    'id'   => $cat->id,
                    'name' => $cat->label(),
                ]),
        ]);
    }

    public function update(Request $request)
    {
        $pageKeys  = array_keys(SystemSetting::employeePageOptions());
        $fieldKeys = array_keys(SystemSetting::publicSearchFieldOptions());

        $validated = $request->validate(array_merge([
            'hotline'                  => 'required|string|max:50',
            'office_hours'             => 'required|string|max:255',
            'public_search_fields'     => 'required|array|min:1',
            'public_search_fields.*'   => 'in:' . implode(',', $fieldKeys),
            'public_search_categories' => 'nullable|array',
            'public_search_categories.*' => 'integer|exists:document_categories,id',
            'employee_pages'           => 'required|array|min:1',
            'employee_pages.*'         => 'in:' . implode(',', $pageKeys),
            'official_signer_user_id'  => 'nullable|exists:users,id',
        ], $this->retentionRules()));

        $settings = SystemSetting::current();
        $settings->update([
            'hotline'                  => $validated['hotline'],
            'office_hours'             => $validated['office_hours'],
            'public_search_fields'     => $validated['public_search_fields'],
            'public_search_categories' => $validated['public_search_categories'] ?? [],
            'employee_pages'           => $validated['employee_pages'],
            'retention_periods'        => DocumentRetentionService::persistable($validated['retention_periods']),
            'official_signer_user_id'  => $validated['official_signer_user_id'] ?: null,
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

    public function updateRetention(Request $request)
    {
        $validated = $request->validate($this->retentionRules());

        $settings = SystemSetting::current();
        $settings->update([
            'retention_periods' => DocumentRetentionService::persistable($validated['retention_periods']),
        ]);

        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => 'Settings Updated',
            'description' => 'Document retention periods were updated by ' . Auth::user()->name . '.',
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', 'Retention periods saved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function retentionRules(): array
    {
        return [
            'retention_periods'          => 'required|array|min:1|max:20',
            'retention_periods.*.amount' => 'required|integer|min:1|max:3650',
            'retention_periods.*.unit'   => 'required|in:days,months',
        ];
    }
}
