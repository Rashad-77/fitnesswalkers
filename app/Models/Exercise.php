<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Exercise extends Model
{
    protected $guarded = [];
    protected $casts = ['is_dummy' => 'boolean'];

    public function equipment(): BelongsTo { return $this->belongsTo(Equipment::class); }

    /** Exercises the gym can actually offer: no equipment needed, or equipment that is not out of order. */
    public function scopeAvailable($q)
    {
        return $q->where(fn ($w) => $w->whereNull('equipment_id')
            ->orWhereHas('equipment', fn ($e) => $e->where('out_of_order', false)));
    }

    public function isAvailable(): bool
    {
        return ! $this->equipment || ! $this->equipment->out_of_order;
    }

    /**
     * The tutorial for this exercise: the link or upload the owner added, or else a file named after the exercise dropped in
     * public/img/exercises (for example "bench-press.gif"). Returns null when there is none yet.
     */
    public function mediaUrl(): ?string
    {
        if ($this->clip_url) return $this->clip_url;
        $slug = Str::slug($this->name);
        foreach (['gif', 'webp', 'mp4', 'webm'] as $ext) {
            if (is_file(public_path("img/exercises/$slug.$ext"))) return "/img/exercises/$slug.$ext";
        }
        return null;
    }

    /** 'image' for a GIF or picture, 'video' for MP4 or WebM. */
    public function mediaKind(): ?string
    {
        $url = $this->mediaUrl();
        if (! $url) return null;
        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        return in_array($ext, ['gif', 'webp', 'png', 'jpg', 'jpeg', 'avif'], true) ? 'image' : 'video';
    }
}
