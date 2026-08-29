<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\RequestType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RequestTypeController extends Controller
{
    public function store(Request $request)
    {
        $validated = $this->validatedPayload($request);

        if (RequestType::nameIsTaken($validated['name'])) {
            throw ValidationException::withMessages([
                'name' => 'That request type name is already saved.',
            ]);
        }

        $type = RequestType::query()->create([
            ...$validated,
            'sort_order' => (int) RequestType::query()->max('sort_order') + 1,
        ]);

        $this->audit($request, 'Request Type Created', "Added request type '{$type->name}'.");

        return back()->with('success', "Request type '{$type->name}' saved to the database.");
    }

    public function update(Request $request, RequestType $requestType)
    {
        $validated = $this->validatedPayload($request);

        if (RequestType::nameIsTaken($validated['name'], $requestType->id)) {
            throw ValidationException::withMessages([
                'name' => 'That request type name is already saved.',
            ]);
        }

        if (! $validated['is_active'] && $requestType->is_active && $requestType->isLastVisibleActive()) {
            throw ValidationException::withMessages([
                'is_active' => 'Keep at least one request type available on the public form.',
            ]);
        }

        $requestType->update($validated);

        $this->audit($request, 'Request Type Updated', "Updated request type '{$requestType->name}'.");

        return back()->with('success', "Request type '{$requestType->name}' saved to the database.");
    }

    public function destroy(Request $request, RequestType $requestType)
    {
        if (RequestType::query()->count() === 1) {
            return back()->with('error', 'Keep at least one request type.');
        }

        if ($requestType->is_active && $requestType->isLastVisibleActive()) {
            return back()->with('error', 'Keep at least one request type available on the public form.');
        }

        $name = $requestType->name;

        if ($requestType->requests()->exists()) {
            $requestType->update(['is_active' => false]);
            $this->audit($request, 'Request Type Hidden', "Hid request type '{$name}' because existing requests still use it.");

            return back()->with('success', "Request type '{$name}' was hidden because existing requests still use it.");
        }

        $requestType->delete();
        $this->audit($request, 'Request Type Deleted', "Removed request type '{$name}'.");

        return back()->with('success', "Request type '{$name}' removed from the database.");
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|distinct|exists:request_types,id',
        ]);

        foreach (array_values($validated['ids']) as $index => $id) {
            RequestType::query()->whereKey($id)->update(['sort_order' => $index + 1]);
        }

        $this->audit($request, 'Request Types Reordered', 'Updated request type order.');

        return back()->with('success', 'Request type order saved.');
    }

    /**
     * @return array{name: string, purpose: string, issues_certificate: bool, is_active: bool}
     */
    protected function validatedPayload(Request $request): array
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'purpose'            => 'required|string|max:255',
            'issues_certificate' => 'required|boolean',
            'is_active'          => 'required|boolean',
        ]);

        return [
            'name'               => trim($validated['name']),
            'purpose'            => trim($validated['purpose']),
            'issues_certificate' => RequestType::booleanValue($validated['issues_certificate']),
            'is_active'          => RequestType::booleanValue($validated['is_active']),
        ];
    }

    protected function audit(Request $request, string $action, string $description): void
    {
        AuditLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $request->ip(),
        ]);
    }
}
