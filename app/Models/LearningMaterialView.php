<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningMaterialView extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'learning_material_id',
        'viewed_at',
        'progress_percent',
        'completed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percent' => 'integer',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<LearningMaterial, $this>
     */
    public function learningMaterial(): BelongsTo
    {
        return $this->belongsTo(LearningMaterial::class);
    }
}
