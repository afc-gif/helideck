<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    /**
     * The table name.
     *
     * @var string
     */
    protected $table = 'sync_logs';

    /**
     * Timestamps
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inspection_id',
        'action',
        'message',
        'payload',
        'inspector_id',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'json',
        'synced_at' => 'datetime',
    ];

    /**
     * Relationship: Sync log belongs to Inspection
     */
    public function inspection()
    {
        return $this->belongsTo(Inspection::class, 'inspection_id');
    }

    /**
     * Relationship: Sync log belongs to Inspector (User)
     */
    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
