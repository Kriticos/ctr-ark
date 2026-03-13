<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procedure extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PUBLISHED = 'published';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sector_id',
        'created_by',
        'current_version_id',
        'title',
        'slug',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Setores vinculados ao procedimento (multi-setor).
     *
     * @return BelongsToMany<Sector, $this>
     */
    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class, 'procedure_sector')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<ProcedureVersion, $this>
     */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ProcedureVersion::class, 'current_version_id');
    }

    /**
     * @return HasMany<ProcedureVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(ProcedureVersion::class)->orderByDesc('version_number');
    }

    /**
     * @return HasMany<ProcedureApprovalAction, $this>
     */
    public function approvalActions(): HasMany
    {
        return $this->hasMany(ProcedureApprovalAction::class)->latest();
    }

    /**
     * @return HasOne<ProcedureApprovalAction, $this>
     */
    public function latestApprovalAction(): HasOne
    {
        return $this->hasOne(ProcedureApprovalAction::class)->latestOfMany();
    }

    /**
     * @return HasMany<ProcedureAudit, $this>
     */
    public function audits(): HasMany
    {
        return $this->hasMany(ProcedureAudit::class)->latest();
    }
}
