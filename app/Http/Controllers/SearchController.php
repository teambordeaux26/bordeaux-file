<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * JSON endpoint for the top-bar global search dropdown.
     */
    public function suggest(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $q = trim($validated['q'] ?? '');

        if ($q === '') {
            return response()->json([
                'q'       => $q,
                'results' => [],
            ]);
        }

        $results = $this->gatherResults($request, $q, 20);

        return response()->json([
            'q'       => $q,
            'results' => $results->values()->all(),
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function gatherResults(Request $request, string $q, int $limit = 20)
    {
        $perSource = max(5, (int) ceil($limit / 2));

        $results = collect()
            ->merge($this->searchDocuments($q, $perSource))
            ->merge($this->searchCertificates($q, $perSource))
            ->merge($this->searchVisitors($q, $perSource));

        if ($request->user()?->role === 'admin') {
            $results = $results
                ->merge($this->searchRequests($q, $perSource))
                ->merge($this->searchUsers($q, $perSource))
                ->merge($this->searchDepartments($q, $perSource));
        }

        return $results->sortByDesc('sort_date')->values()->take($limit);
    }

    private function searchDocuments(string $q, int $limit = 50)
    {
        $statusQuery = str_replace(' ', '_', strtolower($q));

        return Document::with(['category', 'submitter', 'reviewer'])
            ->where(function (Builder $builder) use ($q, $statusQuery) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('tracking_number', 'like', "%{$q}%")
                    ->orWhere('reference_no', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$statusQuery}%")
                    ->orWhere('priority', 'like', "%{$q}%")
                    ->orWhereRaw("REPLACE(status, '_', ' ') LIKE ?", ["%{$q}%"])
                    ->orWhereHas('category', function (Builder $categoryQuery) use ($q) {
                        $categoryQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%");
                    })
                    ->orWhereHas('submitter', function (Builder $userQuery) use ($q) {
                        $userQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('department', 'like', "%{$q}%")
                            ->orWhere('position', 'like', "%{$q}%");
                    })
                    ->orWhereHas('reviewer', function (Builder $userQuery) use ($q) {
                        $userQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($document) => [
                'key'       => "document-{$document->id}",
                'type'      => $document->status === 'archived' ? 'Archived Document' : 'Document',
                'title'     => $document->title,
                'subtitle'  => implode(' · ', array_filter([
                    $document->tracking_number,
                    $document->category?->name,
                    $document->priority,
                    $document->submitter?->name,
                    $document->created_at->format('M d, Y'),
                ])),
                'status'    => ucfirst(str_replace('_', ' ', $document->status)),
                'url'       => route('documents.show', $document->id),
                'external'  => false,
                'sort_date' => $document->created_at->timestamp,
            ]);
    }

    private function searchCertificates(string $q, int $limit = 50)
    {
        return Certificate::with(['visitorLog', 'issuer'])
            ->where(function (Builder $builder) use ($q) {
                $builder->where('certificate_no', 'like', "%{$q}%")
                    ->orWhereHas('visitorLog', function (Builder $visitorQuery) use ($q) {
                        $visitorQuery->where('visitor_name', 'like', "%{$q}%")
                            ->orWhere('visitor_email', 'like', "%{$q}%")
                            ->orWhere('visitor_phone', 'like', "%{$q}%")
                            ->orWhere('purpose', 'like', "%{$q}%")
                            ->orWhere('address', 'like', "%{$q}%");
                    })
                    ->orWhereHas('issuer', function (Builder $userQuery) use ($q) {
                        $userQuery->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('department', 'like', "%{$q}%")
                            ->orWhere('position', 'like', "%{$q}%");
                    });
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($certificate) => [
                'key'       => "certificate-{$certificate->id}",
                'type'      => 'Certificate',
                'title'     => $certificate->certificate_no,
                'subtitle'  => implode(' · ', array_filter([
                    $certificate->visitorLog?->visitor_name,
                    $certificate->visitorLog?->purpose ?? 'Certificate of Appearance',
                    $certificate->issuer?->name,
                    ($certificate->issued_at ?? $certificate->created_at)?->format('M d, Y'),
                ])),
                'status'    => 'Issued',
                'url'       => route('certificates.download', $certificate->id),
                'external'  => true,
                'sort_date' => ($certificate->issued_at ?? $certificate->created_at)?->timestamp ?? 0,
            ]);
    }

    private function searchVisitors(string $q, int $limit = 50)
    {
        return VisitorLog::query()
            ->where(function (Builder $builder) use ($q) {
                $builder->where('visitor_name', 'like', "%{$q}%")
                    ->orWhere('visitor_email', 'like', "%{$q}%")
                    ->orWhere('visitor_phone', 'like', "%{$q}%")
                    ->orWhere('purpose', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            })
            ->latest('time_in')
            ->limit($limit)
            ->get()
            ->map(fn ($visitor) => [
                'key'       => "visitor-{$visitor->id}",
                'type'      => 'Visitor',
                'title'     => $visitor->visitor_name,
                'subtitle'  => implode(' · ', array_filter([
                    $visitor->purpose,
                    $visitor->visitor_phone,
                    $visitor->time_in?->format('M d, Y g:i A'),
                ])),
                'status'    => $visitor->time_out ? 'Checked Out' : 'Checked In',
                'url'       => route('visitors.index'),
                'external'  => false,
                'sort_date' => $visitor->time_in?->timestamp ?? $visitor->created_at->timestamp,
            ]);
    }

    private function searchRequests(string $q, int $limit = 50)
    {
        $statusQuery = str_replace(' ', '_', strtolower($q));

        return DocumentRequest::query()
            ->where(function (Builder $builder) use ($q, $statusQuery) {
                $builder->where('tracking_number', 'like', "%{$q}%")
                    ->orWhere('requester_name', 'like', "%{$q}%")
                    ->orWhere('requester_email', 'like', "%{$q}%")
                    ->orWhere('requester_phone', 'like', "%{$q}%")
                    ->orWhere('requester_address', 'like', "%{$q}%")
                    ->orWhere('request_type', 'like', "%{$q}%")
                    ->orWhere('details', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$statusQuery}%")
                    ->orWhereRaw("REPLACE(status, '_', ' ') LIKE ?", ["%{$q}%"]);
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($request) => [
                'key'       => "request-{$request->id}",
                'type'      => 'Request',
                'title'     => $request->requester_name,
                'subtitle'  => implode(' · ', array_filter([
                    $request->tracking_number,
                    $request->request_type,
                    $request->requester_email,
                    $request->created_at->format('M d, Y'),
                ])),
                'status'    => ucfirst(str_replace('_', ' ', $request->status)),
                'url'       => route('requests.index'),
                'external'  => false,
                'sort_date' => $request->created_at->timestamp,
            ]);
    }

    private function searchUsers(string $q, int $limit = 50)
    {
        return User::query()
            ->where(function (Builder $builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('department', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%")
                    ->orWhere('role', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($user) => [
                'key'       => "user-{$user->id}",
                'type'      => 'User',
                'title'     => $user->name,
                'subtitle'  => implode(' · ', array_filter([
                    $user->email,
                    $user->role,
                    $user->department,
                    $user->position,
                ])),
                'status'    => ucfirst($user->status ?? 'active'),
                'url'       => route('users.index'),
                'external'  => false,
                'sort_date' => $user->created_at?->timestamp ?? 0,
            ]);
    }

    private function searchDepartments(string $q, int $limit = 50)
    {
        return Department::query()
            ->where(function (Builder $builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$q}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($department) => [
                'key'       => "department-{$department->id}",
                'type'      => 'Department',
                'title'     => $department->name,
                'subtitle'  => implode(' · ', array_filter([
                    $department->code,
                    $department->description,
                ])),
                'status'    => ucfirst($department->status ?? 'active'),
                'url'       => route('departments.index'),
                'external'  => false,
                'sort_date' => $department->created_at?->timestamp ?? 0,
            ]);
    }
}
