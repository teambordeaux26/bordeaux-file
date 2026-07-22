<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\VisitorLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
        ]);

        $q        = trim($validated['q'] ?? '');
        $searched = $q !== '';
        $results  = collect();

        if ($searched) {
            $results = $results
                ->merge($this->searchDocuments($q))
                ->merge($this->searchCertificates($q))
                ->merge($this->searchVisitors($q));

            if ($request->user()?->role === 'admin') {
                $results = $results->merge($this->searchRequests($q));
            }
        }

        $results = $results
            ->sortByDesc('sort_date')
            ->values()
            ->all();

        return Inertia::render('Search/Index', [
            'results'  => $results,
            'filters'  => compact('q'),
            'searched' => $searched,
        ]);
    }

    private function searchDocuments(string $q)
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

    private function searchCertificates(string $q)
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

    private function searchVisitors(string $q)
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

    private function searchRequests(string $q)
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
}
