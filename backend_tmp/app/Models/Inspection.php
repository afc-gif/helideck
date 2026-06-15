<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inspection extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'inspector_id',
        'form_data',
        'status',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'form_data' => 'json',
        'synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship: Inspection belongs to Inspector (User)
     */
    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * Relationship: Inspection has many sync logs
     */
    public function syncLogs()
    {
        return $this->hasMany(SyncLog::class, 'inspection_id');
    }

    /**
     * Get the landing site name from form data
     */
    public function getLandingSiteName()
    {
        return $this->form_data['cover']['landing_site_name'] ?? 'Unknown';
    }

    /**
     * Check if inspection is submitted
     */
    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    /**
     * Mark as synced
     */
    public function markSynced(): void
    {
        $this->update(['synced_at' => now()]);
    }
}
