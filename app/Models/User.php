<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentStatusUpdate;
use App\Models\VisitorLog;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'position',
        'phone',
        'status',
        'last_login_at',
        'signing_name',
        'signing_title',
        'signature_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    public function documentsSubmitted(): HasMany
    {
        return $this->hasMany(Document::class, 'submitted_by');
    }

    public function documentsReviewed(): HasMany
    {
        return $this->hasMany(Document::class, 'reviewed_by');
    }

    public function documentsHandled(): HasMany
    {
        return $this->hasMany(Document::class, 'handled_by');
    }

    /**
     * @return list<array{id: int, name: string, role: string, department: ?string, position: string, label: string}>
     */
    public static function staffOptions(?int $exceptId = null): array
    {
        return static::query()
            ->where('status', 'active')
            ->whereIn('role', ['admin', 'employee'])
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->orderBy('name')
            ->get(['id', 'name', 'role', 'department', 'position'])
            ->map(fn (self $user) => [
                'id'         => $user->id,
                'name'       => $user->name,
                'role'       => $user->role,
                'department' => $user->department,
                'position'   => $user->position ?: 'Staff',
                'label'      => $user->name.' · '.($user->position ?: ucfirst((string) $user->role)),
            ])
            ->all();
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(DocumentStatusUpdate::class, 'updated_by');
    }

    public function documentRequestsProcessed(): HasMany
    {
        return $this->hasMany(DocumentRequest::class, 'processed_by');
    }

    public function visitorLogs(): HasMany
    {
        return $this->hasMany(VisitorLog::class, 'recorded_by');
    }

    public function certificatesIssued(): HasMany
    {
        return $this->hasMany(Certificate::class, 'issued_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
