<?php

use App\Models\Certificate;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function reportStaff(): User
{
    return User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
    ]);
}

it('filters document reports to a custom from and to date', function () {
    $this->withoutVite();

    $staff = reportStaff();

    $inRange = Document::query()->create([
        'tracking_number' => 'TRK-IN',
        'title' => 'In range memo',
        'status' => 'pending',
        'submitted_by' => $staff->id,
    ]);
    $inRange->forceFill([
        'created_at' => '2026-03-10 09:00:00',
        'updated_at' => '2026-03-10 09:00:00',
    ])->saveQuietly();

    $outOfRange = Document::query()->create([
        'tracking_number' => 'TRK-OUT',
        'title' => 'Out of range memo',
        'status' => 'pending',
        'submitted_by' => $staff->id,
    ]);
    $outOfRange->forceFill([
        'created_at' => '2026-04-20 09:00:00',
        'updated_at' => '2026-04-20 09:00:00',
    ])->saveQuietly();

    $this->actingAs($staff)
        ->get('/documents?report_period=custom&report_from=2026-03-01&report_to=2026-03-15')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Documents/Index')
            ->where('report.period', 'custom')
            ->where('report.from', '2026-03-01')
            ->where('report.to', '2026-03-15')
            ->where('report.stats.0.label', 'Submitted')
            ->where('report.stats.0.value', 1)
        );

    $csv = $this->actingAs($staff)
        ->get('/documents/reports/export?period=custom&from=2026-03-01&to=2026-03-15&format=csv');

    $csv->assertOk();
    $content = $csv->streamedContent();

    expect($content)
        ->toContain('In range memo')
        ->and($content)->not->toContain('Out of range memo');
});

it('filters visitor reports to a custom from and to date', function () {
    $this->withoutVite();

    $staff = reportStaff();

    VisitorLog::query()->create([
        'visitor_name' => 'Ana Santos',
        'purpose' => 'Meeting',
        'time_in' => '2026-03-08 10:00:00',
        'recorded_by' => $staff->id,
    ]);

    VisitorLog::query()->create([
        'visitor_name' => 'Pedro Cruz',
        'purpose' => 'Inquiry',
        'time_in' => '2026-04-02 10:00:00',
        'recorded_by' => $staff->id,
    ]);

    $this->actingAs($staff)
        ->get('/visitors?report_period=custom&report_from=2026-03-01&report_to=2026-03-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Visitors/Index')
            ->where('report.period', 'custom')
            ->where('report.from', '2026-03-01')
            ->where('report.to', '2026-03-31')
            ->where('report.stats.0.label', 'Total visitors')
            ->where('report.stats.0.value', 1)
        );

    $csv = $this->actingAs($staff)
        ->get('/visitors/reports/export?period=custom&from=2026-03-01&to=2026-03-31&format=csv');

    $csv->assertOk();
    $content = $csv->streamedContent();

    expect($content)
        ->toContain('Ana Santos')
        ->and($content)->not->toContain('Pedro Cruz');
});

it('filters certificate reports to a custom from and to date', function () {
    $this->withoutVite();

    $staff = reportStaff();

    $inRangeVisitor = VisitorLog::query()->create([
        'visitor_name' => 'Ana Santos',
        'purpose' => 'Barangay clearance',
        'time_in' => '2026-03-08 10:00:00',
        'recorded_by' => $staff->id,
    ]);

    $outOfRangeVisitor = VisitorLog::query()->create([
        'visitor_name' => 'Pedro Cruz',
        'purpose' => 'Indigency',
        'time_in' => '2026-04-02 10:00:00',
        'recorded_by' => $staff->id,
    ]);

    Certificate::query()->create([
        'visitor_log_id' => $inRangeVisitor->id,
        'certificate_no' => 'CERT-IN',
        'issued_by' => $staff->id,
        'issued_at' => '2026-03-08 11:00:00',
    ]);

    Certificate::query()->create([
        'visitor_log_id' => $outOfRangeVisitor->id,
        'certificate_no' => 'CERT-OUT',
        'issued_by' => $staff->id,
        'issued_at' => '2026-04-02 11:00:00',
    ]);

    $this->actingAs($staff)
        ->get('/certificates?report_period=custom&report_from=2026-03-01&report_to=2026-03-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Certificates/Index')
            ->where('report.period', 'custom')
            ->where('report.from', '2026-03-01')
            ->where('report.to', '2026-03-31')
            ->where('report.stats.0.label', 'Certificates issued')
            ->where('report.stats.0.value', 1)
        );

    $csv = $this->actingAs($staff)
        ->get('/certificates/reports/export?period=custom&from=2026-03-01&to=2026-03-31&format=csv');

    $csv->assertOk();
    $content = $csv->streamedContent();

    expect($content)
        ->toContain('CERT-IN')
        ->and($content)->not->toContain('CERT-OUT');
});

it('does not preview a report until a date filter is set', function () {
    $this->withoutVite();

    $this->actingAs(reportStaff())
        ->get('/documents')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Documents/Index')
            ->where('report.from', '')
            ->where('report.to', '')
            ->where('report.stats', [])
        );
});

it('filters document reports to a previous month', function () {
    $this->withoutVite();

    $staff = reportStaff();

    $inRange = Document::query()->create([
        'tracking_number' => 'TRK-AUG',
        'title' => 'August memo',
        'status' => 'pending',
        'submitted_by' => $staff->id,
    ]);
    $inRange->forceFill([
        'created_at' => '2026-08-12 09:00:00',
        'updated_at' => '2026-08-12 09:00:00',
    ])->saveQuietly();

    $this->actingAs($staff)
        ->get('/documents?report_period=month-2026-08')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Documents/Index')
            ->where('report.period', 'month-2026-08')
            ->where('report.from', '2026-08-01')
            ->where('report.to', '2026-08-31')
            ->where('report.stats.0.value', 1)
        );
});

it('limits document reports to the selected category', function () {
    $this->withoutVite();

    $staff = reportStaff();
    $legislative = DocumentCategory::query()->where('name', 'Legislative Documents')->whereNull('parent_id')->firstOrFail();
    $minutes = DocumentCategory::query()->where('name', 'Meeting Minutes')->whereNull('parent_id')->firstOrFail();
    $committee = DocumentCategory::query()->where('name', 'Committee Meetings')->firstOrFail();
    $executive = DocumentCategory::query()->where('name', 'Executive and Administrative Documents')->whereNull('parent_id')->firstOrFail();

    foreach ([
        ['TRK-LEG', 'Ordinance draft', $legislative->id],
        ['TRK-MIN', 'Committee notes', $committee->id],
        ['TRK-EXE', 'Executive memo', $executive->id],
    ] as [$tracking, $title, $categoryId]) {
        $document = Document::query()->create([
            'tracking_number' => $tracking,
            'title' => $title,
            'status' => 'pending',
            'category_id' => $categoryId,
            'submitted_by' => $staff->id,
        ]);
        $document->forceFill([
            'created_at' => '2026-03-10 09:00:00',
            'updated_at' => '2026-03-10 09:00:00',
        ])->saveQuietly();
    }

    $this->actingAs($staff)
        ->get('/documents?category_id='.$legislative->id.'&report_period=custom&report_from=2026-03-01&report_to=2026-03-15')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Documents/Index')
            ->where('report.stats.0.value', 1)
            ->where('report.breakdown.0.label', 'Legislative Documents')
            ->where('documents.data', fn ($rows) => collect($rows)->pluck('title')->all() === ['Ordinance draft'])
        );

    $csv = $this->actingAs($staff)
        ->get('/documents/reports/export?period=custom&from=2026-03-01&to=2026-03-15&format=csv&category_id='.$legislative->id);

    $csv->assertOk();
    $content = $csv->streamedContent();

    expect($content)
        ->toContain('Ordinance draft')
        ->and($content)->not->toContain('Executive memo')
        ->and($content)->not->toContain('Committee notes');

    $this->actingAs($staff)
        ->get('/documents?category_id='.$minutes->id.'&report_period=custom&report_from=2026-03-01&report_to=2026-03-15')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.stats.0.value', 1)
            ->where('report.breakdown.0.label', 'Meeting Minutes — Committee Meetings')
            ->where('documents.data.0.title', 'Committee notes')
        );
});

it('requires from and to dates when generating a custom report', function () {
    $staff = reportStaff();

    $this->actingAs($staff)
        ->get('/documents/reports/export?period=custom&format=csv')
        ->assertSessionHasErrors(['from', 'to']);
});
