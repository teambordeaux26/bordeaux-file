<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'hotline',
        'office_hours',
        'public_search_fields',
        'public_search_categories',
        'employee_pages',
    ];

    protected $casts = [
        'public_search_fields'     => 'array',
        'public_search_categories' => 'array',
        'employee_pages'           => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], static::defaults());
    }

    public static function defaults(): array
    {
        return [
            'hotline'                  => '(052) 555-0198',
            'office_hours'             => 'Monday to Friday, 8:00 AM – 5:00 PM',
            'public_search_fields'     => static::defaultPublicSearchFields(),
            'public_search_categories' => [],
            'employee_pages'           => static::defaultEmployeePages(),
        ];
    }

    public static function defaultPublicSearchFields(): array
    {
        return ['title', 'tracking_number', 'reference_no', 'description', 'category'];
    }

    public static function defaultEmployeePages(): array
    {
        return array_keys(static::employeePageOptions());
    }

    public static function employeePageOptions(): array
    {
        return [
            'dashboard'    => 'Dashboard',
            'documents'    => 'Documents',
            'tracking'     => 'Tracking',
            'visitors'     => 'Visitors Log',
            'certificates' => 'Certificates',
            'search'       => 'Search',
            'archive'      => 'Archive',
        ];
    }

    public static function publicSearchFieldOptions(): array
    {
        return [
            'title'           => 'Document Title',
            'tracking_number' => 'Tracking Number',
            'reference_no'    => 'Reference Number',
            'description'     => 'Description',
            'category'        => 'Category',
        ];
    }

    public static function routePageMap(): array
    {
        return [
            'dashboard'             => 'dashboard',
            'documents.index'       => 'documents',
            'documents.upload'      => 'documents',
            'documents.store'       => 'documents',
            'documents.show'        => 'documents',
            'documents.file'        => 'documents',
            'tracking.index'        => 'tracking',
            'visitors.index'        => 'visitors',
            'visitors.store'        => 'visitors',
            'certificates.index'    => 'certificates',
            'certificates.generate' => 'certificates',
            'certificates.download' => 'certificates',
            'search.index'          => 'search',
            'archive.index'         => 'archive',
        ];
    }

    public function allowsEmployeePage(string $pageKey): bool
    {
        $allowed = $this->employee_pages ?? static::defaultEmployeePages();

        return in_array($pageKey, $allowed, true);
    }

    public function allowsEmployeeRoute(?string $routeName): bool
    {
        if (! $routeName) {
            return true;
        }

        $pageKey = static::routePageMap()[$routeName] ?? null;

        if (! $pageKey) {
            return true;
        }

        return $this->allowsEmployeePage($pageKey);
    }

    public function toSiteProps(): array
    {
        return [
            'hotline'      => $this->hotline,
            'office_hours' => $this->office_hours,
        ];
    }
}
