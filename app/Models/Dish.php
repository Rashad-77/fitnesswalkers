<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A dish people actually cook and eat, such as "Red rice with fish curry and mallum", made from catalogue foods. */
class Dish extends Model
{
    protected $guarded = [];
    protected $casts = ['active' => 'boolean', 'is_dummy' => 'boolean'];

    public const KINDS = ['meal' => 'Meal', 'snack' => 'Snack', 'dessert' => 'Dessert'];
    public const SLOTS = ['breakfast' => 'Breakfast', 'lunch' => 'Lunch', 'dinner' => 'Dinner', 'snack' => 'Snack'];
    public const TAGS = ['quick' => 'Quick (about 20 minutes)', 'batch' => 'Cook once, eat twice', 'weekend' => 'Takes longer, best at the weekend'];

    public function items(): HasMany { return $this->hasMany(DishItem::class)->orderBy('sort')->orderBy('id'); }

    public function slotList(): array { return $this->csv($this->slots); }
    public function tagList(): array { return $this->csv($this->tags); }
    public function allergenList(): array { return $this->csv($this->allergens); }
    public function avoidList(): array { return $this->csv($this->avoid_tags); }
    private function csv(?string $v): array { return array_values(array_filter(array_map('trim', explode(',', (string) $v)))); }

    public function needList(): array { return $this->csv($this->needs); }

    /** Work out what the dish needs (dairy, egg, meat), its allergens and any beef or pork from its foods. Call after changing its items. */
    public function refreshFromItems(): void
    {
        $foods = $this->items()->with('food')->get()->pluck('food')->filter();
        $join = fn ($c) => implode(',', $c->unique()->values()->all()) ?: null;
        $this->forceFill([
            'needs' => $join($foods->pluck('diet')->reject(fn ($d) => $d === 'vegan')),
            'allergens' => $join($foods->flatMap(fn ($f) => $f->allergenList())),
            'avoid_tags' => $join($foods->flatMap(fn ($f) => $f->tagList())),
        ])->save();
    }
}
