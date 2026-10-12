<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Food};
use App\Services\PriceUpdater;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public const CATEGORIES = ['grain', 'protein', 'legume', 'dairy', 'veg', 'fruit', 'fat'];
    public const DIET_TYPES = ['vegan', 'vegetarian', 'non_veg'];

    public function index(Request $r)
    {
        $foods = Food::when($r->query('category'), fn ($q, $c) => $q->where('category', $c))->orderBy('category')->orderBy('name')->get();
        return view('admin.foods.index', [
            'foods' => $foods, 'category' => $r->query('category'), 'cats' => self::CATEGORIES, 'types' => self::DIET_TYPES,
            'edit' => $r->query('edit') ? Food::find($r->query('edit')) : null,
            'feedConfigured' => (bool) config('fitness.price_feed_url'),
            'oldest' => Food::min('price_updated_on'),
        ]);
    }

    public function store(Request $r)
    {
        $f = Food::create($this->data($r) + ['price_updated_on' => today()]);
        AuditLog::record('Added food', $f->name);
        return redirect()->route('admin.foods.index')->with('ok', 'Food added.');
    }

    public function update(Request $r, Food $food)
    {
        $old = (float) $food->price_per_kg;
        if ($r->has('quick')) { // quick price edit from the table
            $d = $r->validate(['price_per_kg' => 'required|numeric|min:1']);
            $food->update($d + ['price_locked' => $r->boolean('price_locked'), 'price_updated_on' => today(), 'is_dummy' => false]);
        } else {
            $food->update($this->data($r) + ['price_updated_on' => today(), 'is_dummy' => false]);
        }
        if ($old != (float) $food->price_per_kg) {
            AuditLog::record('Changed food price', $food->name, "Rs. $old -> Rs. {$food->price_per_kg} per kg");
        }
        return redirect()->route('admin.foods.index')->with('ok', $food->name.' updated.');
    }

    public function destroy(Food $food)
    {
        AuditLog::record('Deleted food', $food->name);
        $food->delete();
        return back()->with('ok', 'Food deleted. Existing member plans keep their saved copy.');
    }

    /** CSV with two columns: name, price_per_kg (header row optional). */
    public function import(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:csv,txt|max:512']);
        $rows = [];
        if (($h = fopen($r->file('file')->getRealPath(), 'r')) !== false) {
            while (($line = fgetcsv($h)) !== false) {
                if (count($line) < 2 || ! is_numeric(trim($line[1]))) continue; // skips header and bad lines
                $rows[] = ['name' => trim($line[0]), 'price_per_kg' => (float) $line[1]];
            }
            fclose($h);
        }
        $res = PriceUpdater::apply($rows);
        AuditLog::record('Imported food prices', null, "{$res['updated']} updated");
        return back()->with('ok', "Updated {$res['updated']} prices.")->with('warn', $this->summary($res));
    }

    public function feed()
    {
        $res = PriceUpdater::fromFeed();
        if ($res['error']) return back()->with('warn', $res['error']);
        AuditLog::record('Ran price feed', null, "{$res['updated']} updated");
        return back()->with('ok', "Updated {$res['updated']} prices from the feed.")->with('warn', $this->summary($res));
    }

    private function summary(array $res): ?string
    {
        $bits = [];
        if ($res['skipped_locked']) $bits[] = $res['skipped_locked'].' locked by you';
        if ($res['rejected']) $bits[] = count($res['rejected']).' rejected as suspicious ('.implode('; ', array_slice($res['rejected'], 0, 3)).')';
        if ($res['unmatched']) $bits[] = count($res['unmatched']).' names not found ('.implode(', ', array_slice($res['unmatched'], 0, 5)).')';
        return $bits ? 'Skipped: '.implode('. ', $bits).'.' : null;
    }

    private function data(Request $r): array
    {
        $d = $r->validate([
            'name' => 'required|max:120', 'category' => 'required|in:'.implode(',', self::CATEGORIES), 'diet_type' => 'required|in:'.implode(',', self::DIET_TYPES),
            'allergens' => 'array', 'allergens.*' => 'in:'.implode(',', \App\Services\DietGenerator::ALLERGENS),
            'meal_tags' => 'array', 'meal_tags.*' => 'in:breakfast,main,snack',
            'kcal_100g' => 'required|numeric|min:0', 'protein_100g' => 'required|numeric|min:0', 'carbs_100g' => 'required|numeric|min:0', 'fat_100g' => 'required|numeric|min:0',
            'price_per_kg' => 'required|numeric|min:1',
        ]);
        $d['allergens'] = implode(',', $d['allergens'] ?? []) ?: null;
        $d['meal_tags'] = implode(',', $d['meal_tags'] ?? ['main']);
        $d['price_locked'] = $r->boolean('price_locked');
        return $d;
    }
}
