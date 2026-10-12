<?php

/**
 * The food catalogue the meal planner draws from.
 *
 * SOURCE: every food here is named in Sri Lanka's Food Based Dietary Guidelines, 3rd edition (2021), Practitioner's Handbook,
 * Ministry of Health Nutrition Division with FAO, which is the guarantee that it is eaten in Sri Lanka. The household serving
 * sizes (for example 1/2 cup cooked rice, 3 tbsp dhal, 30 g fish) are the handbook's.
 *
 * Calories and protein per serving are APPROXIMATE estimates (typical values for that serving) and are shown to members as
 * "about". Replace them with a Sri Lankan food composition table when one can be licensed. The owner can edit any food in
 * Library > Foods.
 *
 * Columns: name, local name, group, sub-group, serving quantity, unit, unit (plural), grams per serving, kcal, protein g.
 * Options: diet (vegan|dairy|egg|meat), allergens (csv), tags (beef|pork), meal_tags (breakfast|main|snack), whole_grain, limited, note.
 */
$rows = [];
$add = function (string $name, ?string $local, string $grp, ?string $sub, float $per, string $unit, string $plural, ?float $g, int $kcal, float $p, array $o = []) use (&$rows) {
    $rows[] = array_merge([
        'name' => $name, 'local_name' => $local, 'grp' => $grp, 'sub' => $sub, 'per_serving' => $per, 'unit' => $unit, 'unit_plural' => $plural,
        'serving_g' => $g, 'kcal_serving' => $kcal, 'protein_serving' => $p, 'diet' => 'vegan', 'allergens' => null, 'tags' => null,
        'meal_tags' => 'main', 'whole_grain' => false, 'limited' => false, 'note' => null,
    ], $o);
};

/* ---------------------------------------------------------------- cereals and starchy foods (8-13 servings a day) */
$whole = ['whole_grain' => true];
$add('Parboiled rice, cooked (red or white)', 'Thambapu haal', 'cereal', 'rice', .5, 'cup', 'cups', 65, 85, 2, $whole);
$add('Red rice (kekulu), cooked', 'Kekulu', 'cereal', 'rice', .5, 'cup', 'cups', 65, 85, 2, $whole);
$add('Traditional rice, cooked (Suwendal, Heenati, Madathawalu)', null, 'cereal', 'rice', .5, 'cup', 'cups', 65, 85, 2, $whole);
$add('White rice, cooked', null, 'cereal', 'rice', .5, 'cup', 'cups', 65, 85, 2, ['limited' => true]);
$add('Finger millet (kurakkan), cooked', 'Kurakkan', 'cereal', 'millet', .5, 'cup', 'cups', 65, 80, 2, $whole);
$add('Proso millet (meneri), cooked', 'Meneri', 'cereal', 'millet', .5, 'cup', 'cups', 65, 80, 2, $whole);
$add('Foxtail millet (thana haal), cooked', 'Thana haal', 'cereal', 'millet', .5, 'cup', 'cups', 65, 80, 2, $whole);
$add('Oats, rolled or steel-cut, cooked', null, 'cereal', 'grain', .5, 'cup', 'cups', null, 80, 3, $whole + ['allergens' => 'gluten', 'meal_tags' => 'breakfast', 'note' => 'Serving size assumed at 1/2 cup cooked.']);
$add('String hoppers (rice flour)', 'Indi appa', 'cereal', 'prepared', 2, 'string hopper', 'string hoppers', 20, 70, 1.5, ['meal_tags' => 'breakfast,main']);
$add('Hoppers', 'Appa', 'cereal', 'prepared', 1, 'hopper', 'hoppers', 25, 45, 1, ['meal_tags' => 'breakfast,main']);
$add('Roti, whole wheat (atta)', null, 'cereal', 'prepared', .5, 'roti', 'rotis', null, 55, 2, $whole + ['allergens' => 'gluten', 'meal_tags' => 'breakfast,main']);
$add('Roti, white flour', null, 'cereal', 'prepared', .5, 'roti', 'rotis', null, 55, 1.5, ['allergens' => 'gluten', 'limited' => true, 'meal_tags' => 'breakfast,main']);
$add('Bread, whole wheat', null, 'cereal', 'prepared', 1, 'slice', 'slices', 30, 75, 3, $whole + ['allergens' => 'gluten', 'meal_tags' => 'breakfast,snack']);
$add('Bread, white', null, 'cereal', 'prepared', 1, 'slice', 'slices', 30, 75, 2.5, ['allergens' => 'gluten', 'limited' => true, 'meal_tags' => 'breakfast']);
$add('Pittu (rice flour)', 'Pittu', 'cereal', 'prepared', .25, 'large pittu', 'large pittu', null, 80, 2, ['meal_tags' => 'breakfast,main', 'note' => '1/4 of a large pittu (3 cm high, 5 cm across).']);
$add('Dosai', 'Dosai', 'cereal', 'prepared', 1, 'dosai', 'dosai', 22, 55, 1.5, ['meal_tags' => 'breakfast,main']);
$add('Corn on the cob, boiled', null, 'cereal', 'grain', 1, 'cob', 'cobs', null, 90, 3, $whole + ['meal_tags' => 'snack']);
$add('Sweet potato, boiled', 'Batala', 'cereal', 'starchy', .5, 'cup', 'cups', null, 80, 1, ['meal_tags' => 'breakfast,main,snack']);
$add('Manioc, boiled', 'Manioca', 'cereal', 'starchy', .5, 'cup', 'cups', null, 80, 1, ['meal_tags' => 'breakfast,main,snack']);
$add('Potato, boiled', null, 'cereal', 'starchy', 1, 'medium potato', 'medium potatoes', 100, 80, 2, ['meal_tags' => 'main']);
$add('Yam, boiled (kiri ala, raja ala, innala)', 'Ala', 'cereal', 'starchy', .5, 'cup', 'cups', null, 80, 1, ['meal_tags' => 'breakfast,main']);
$add('Breadfruit, boiled', 'Del', 'cereal', 'starchy', .5, 'cup', 'cups', null, 75, 1, ['meal_tags' => 'main']);
$add('Jackfruit, boiled or cooked', 'Kos', 'cereal', 'starchy', .5, 'cup', 'cups', null, 75, 1.5, ['meal_tags' => 'main']);

/* ---------------------------------------------------------------- vegetables and green leaves (3-5 servings a day) */
$v = ['meal_tags' => 'main'];
$add('Pumpkin', 'Wattakka', 'veg', 'vita', .5, 'cup', 'cups', null, 25, 1, $v);
$add('Carrot', null, 'veg', 'vita', .5, 'cup', 'cups', null, 25, .5, ['meal_tags' => 'main,snack']);
$add('Butternut squash', null, 'veg', 'vita', .5, 'cup', 'cups', null, 30, 1, $v);
foreach ([
    ['Beans', null], ['Long beans', 'Me karal'], ['Snake gourd', 'Pathola'], ['Ridge gourd', 'Wetakolu'], ['Drumstick', 'Murunga'],
    ['Bitter gourd', 'Karawila'], ['Brinjal', 'Wambatu'], ['Banana flower', 'Kehelmuwa'], ['Capsicum', 'Malumiris'], ['Wing beans', 'Dambala'],
    ['Tomato', null], ['Cauliflower', null], ['Beetroot', null], ['Radish', null], ['Knol-khol', null], ['Lasia', 'Kohila ala'], ['Lotus root', 'Nelum ala'],
] as [$n, $l]) {
    $add($n, $l, 'veg', 'other', .5, 'cup', 'cups', null, 25, 1, $v);
}
$add('Cucumber', null, 'veg', 'other', .5, 'cup', 'cups', null, 10, .5, ['meal_tags' => 'main,snack', 'note' => 'Listed as a vegetable stick snack in the handbook.']);
foreach ([
    ['Gotukola', 'Gotukola'], ['Mukunuwenna', 'Mukunuwenna'], ['Kankun', 'Kankun'], ['Thampala', 'Thampala'], ['Sarana', 'Sarana'],
    ['Kathurumurunga', 'Kathurumurunga'], ['Drumstick leaves', 'Murunga kola'], ['Spinach', null], ['Beet leaves', null],
    ['Radish leaves', null], ['Pumpkin leaves', null], ['Manioc leaves', null],
] as [$n, $l]) {
    $add($n.' (green leaves)', $l, 'veg', 'leaf', .5, 'cup', 'cups', null, 15, 1.5, $v);
}

/* ---------------------------------------------------------------- fruit (2-3 servings a day) */
$fs = ['meal_tags' => 'breakfast,snack'];
$add('Banana', 'Kesel', 'fruit', 'high', 1, 'medium banana', 'medium bananas', null, 90, 1, $fs);
$add('Anoda', 'Anoda', 'fruit', 'high', .5, 'cup', 'cups', null, 80, 1, $fs);
$add('Dates', null, 'fruit', 'high', 2, 'date', 'dates', null, 45, .4, $fs);
$add('Raisins', null, 'fruit', 'high', 4, 'tsp', 'tsp', null, 40, .4, $fs);
$add('Bael fruit', 'Beli', 'fruit', 'high', .5, 'small bael fruit', 'small bael fruit', null, 60, 1, $fs);
$add('Papaya', 'Papol', 'fruit', 'medium', 2, 'cup cubed', 'cups cubed', null, 90, 1, $fs);
$add('Mango', 'Amba', 'fruit', 'medium', 1, 'medium mango', 'medium mangoes', null, 90, 1, $fs);
$add('Apple', null, 'fruit', 'medium', 1, 'small apple', 'small apples', null, 60, .3, $fs);
$add('Jambu', 'Jambu', 'fruit', 'medium', 12, 'jambu', 'jambu', null, 40, .5, $fs);
$add('Guava', 'Pera', 'fruit', 'medium', 1.5, 'small guava', 'small guavas', null, 60, 2, $fs);
$add('Orange', null, 'fruit', 'medium', 1, 'small orange', 'small oranges', null, 45, 1, $fs);
$add('Pomegranate', null, 'fruit', 'medium', 1, 'medium pomegranate', 'medium pomegranates', null, 80, 1.5, $fs);
$add('Pear', null, 'fruit', 'medium', 1, 'small pear', 'small pears', null, 55, .4, $fs);
$add('Pineapple', 'Annasi', 'fruit', 'medium', 2, 'slice', 'slices', null, 80, .5, $fs);
$add('Wood apple', 'Divul', 'fruit', 'medium', 1, 'small wood apple', 'small wood apples', null, 70, 2, $fs);
$add('Grapes', null, 'fruit', 'medium', 8, 'grape', 'grapes', null, 40, .4, $fs);
$add('Watermelon', 'Komadu', 'fruit', 'low', 1, 'cup', 'cups', null, 45, 1, $fs + ['note' => 'Serving size assumed; the handbook says low-carbohydrate fruit needs no limit.']);
$add('Avocado', null, 'fruit', 'fat', .33, 'medium avocado', 'medium avocados', 50, 80, 1, $fs);

/* ---------------------------------------------------------------- pulses (3-5 servings a day) */
$pm = ['meal_tags' => 'breakfast,main'];
$add('Dhal, cooked', 'Parippu', 'pulse', 'lentil', 3, 'tbsp', 'tbsp', null, 55, 4, $pm);
$add('Kadala parippu (split chickpea), cooked', 'Kadala parippu', 'pulse', 'lentil', 3, 'tbsp', 'tbsp', null, 55, 4, $pm);
$add('Mung parippu (split green gram), cooked', 'Mung parippu', 'pulse', 'lentil', 3, 'tbsp', 'tbsp', null, 50, 4, $pm);
$add('Chickpea (kadala), boiled', 'Kadala', 'pulse', 'bean', .5, 'cup', 'cups', 75, 120, 6.5, ['meal_tags' => 'main,snack']);
$add('Cowpea, boiled', null, 'pulse', 'bean', .5, 'cup', 'cups', 75, 90, 6, ['meal_tags' => 'main']);
$add('Green gram (mung), boiled', 'Mung ata', 'pulse', 'bean', .5, 'cup', 'cups', 75, 80, 5.5, ['meal_tags' => 'breakfast,main,snack']);
$add('Black gram (ulundu), boiled', 'Ulundu', 'pulse', 'bean', .5, 'cup', 'cups', null, 80, 6, ['meal_tags' => 'main', 'note' => 'Serving size assumed at 1/2 cup.']);
$add('Horse gram (kollu), boiled', 'Kollu', 'pulse', 'bean', .5, 'cup', 'cups', null, 90, 5.5, ['meal_tags' => 'main', 'note' => 'Serving size assumed at 1/2 cup.']);
$add('Soya beans, boiled', null, 'pulse', 'soy', .5, 'cup', 'cups', 75, 130, 12, ['allergens' => 'soy', 'meal_tags' => 'main']);

/* ---------------------------------------------------------------- fish, seafood, poultry and lean meat (2-4 servings a day) */
$m = ['diet' => 'meat', 'meal_tags' => 'main'];
$add('Fish, fresh (any local fish, e.g. tuna, paraw, mullet)', null, 'protein', 'fish', 30, 'g', 'g', 30, 40, 7, $m + ['allergens' => 'fish']);
$add('Tuna (kelawalla)', 'Kelawalla', 'protein', 'fish', 30, 'g', 'g', 30, 45, 8, $m + ['allergens' => 'fish']);
$add('Prawns', 'Issa', 'protein', 'shellfish', 30, 'g', 'g', 30, 30, 7, $m + ['allergens' => 'shellfish']);
$add('Cuttlefish', null, 'protein', 'shellfish', 30, 'g', 'g', 30, 25, 5, $m + ['allergens' => 'shellfish']);
$add('Crab', 'Kakuluwo', 'protein', 'shellfish', 30, 'g', 'g', 30, 30, 6, $m + ['allergens' => 'shellfish']);
$add('Chicken, skinless', null, 'protein', 'poultry', 30, 'g', 'g', 30, 50, 9, $m);
$add('Beef, lean', null, 'protein', 'red', 30, 'g', 'g', 30, 70, 8, $m + ['tags' => 'beef']);
$add('Pork, lean', null, 'protein', 'red', 30, 'g', 'g', 30, 60, 8, $m + ['tags' => 'pork']);
$add('Mutton, lean', null, 'protein', 'red', 30, 'g', 'g', 30, 70, 8, $m);
$add('Dried sprats', 'Haalmasso', 'protein', 'dried', 15, 'g', 'g', 15, 45, 8, $m + ['allergens' => 'fish', 'note' => '15 g is about 9 to 10 sprats.']);
$add('Dried fish', null, 'protein', 'dried', 15, 'g', 'g', 15, 40, 9, $m + ['allergens' => 'fish', 'note' => '15 g is one matchbox-size piece.']);

/* ---------------------------------------------------------------- egg (1 a day) */
$add('Egg, hen', null, 'egg', null, 1, 'egg', 'eggs', null, 70, 6, ['diet' => 'egg', 'allergens' => 'egg', 'meal_tags' => 'breakfast,main,snack']);
$add('Egg, duck', null, 'egg', null, 1, 'duck egg', 'duck eggs', null, 130, 9, ['diet' => 'egg', 'allergens' => 'egg', 'limited' => true, 'meal_tags' => 'breakfast,main', 'note' => 'Larger than a hen egg; counted as one serving.']);

/* ---------------------------------------------------------------- fresh milk and fermented milk products (1/2 to 1 serving a day) */
$d = ['diet' => 'dairy', 'allergens' => 'dairy', 'meal_tags' => 'breakfast,snack'];
$add('Fresh milk', null, 'dairy', null, 1, 'cup', 'cups', null, 125, 6.5, $d + ['note' => 'A cup is 200 ml.']);
$add('Curd', 'Meekiri', 'dairy', null, .5, 'cup', 'cups', 100, 90, 4, $d + ['meal_tags' => 'breakfast,main,snack', 'note' => 'Half a cup is about 100 g.']);
$add('Yoghurt, unsweetened', null, 'dairy', null, 1, 'cup', 'cups', 100, 70, 4, $d);
$add('Cheese', null, 'dairy', null, 15, 'g', 'g', 15, 55, 3.5, $d);

/* ---------------------------------------------------------------- nuts and oily seeds (2 servings a day) */
$ns = ['meal_tags' => 'breakfast,snack'];
$add('Peanuts', null, 'nuts', null, 1, 'tbsp', 'tbsp', 15, 85, 3.8, $ns + ['allergens' => 'nuts']);
$add('Cashew nuts', 'Cadju', 'nuts', null, 7, 'cashew', 'cashews', null, 60, 2, $ns + ['allergens' => 'nuts']);
$add('Gingelly (sesame) seeds', 'Thala', 'nuts', null, 1, 'tbsp', 'tbsp', 15, 85, 2.6, $ns + ['allergens' => 'sesame']);
$add('Pumpkin seeds', null, 'nuts', null, 1, 'tbsp', 'tbsp', 15, 85, 4.5, $ns);

/* ---------------------------------------------------------------- coconut (3-6 tbsp a day) */
$add('Scraped coconut', 'Pol', 'coconut', null, 1, 'tbsp', 'tbsp', null, 35, .3, ['meal_tags' => 'breakfast,main']);
$add('Coconut milk', 'Pol kiri', 'coconut', null, 1, 'tbsp', 'tbsp', null, 35, .3, ['meal_tags' => 'main']);

/* ---------------------------------------------------------------- oils and fats (1-3 tsp a day) */
$o = ['meal_tags' => 'main'];
$add('Coconut oil', 'Pol tel', 'oil', null, 1, 'tsp', 'tsp', 5, 45, 0, $o);
$add('Gingelly oil', 'Thala tel', 'oil', null, 1, 'tsp', 'tsp', 5, 45, 0, $o + ['allergens' => 'sesame']);
$add('Corn oil', null, 'oil', null, 1, 'tsp', 'tsp', 5, 45, 0, $o);
$add('Ghee', null, 'oil', null, 1, 'tsp', 'tsp', 5, 40, 0, $o + ['diet' => 'dairy', 'allergens' => 'dairy', 'limited' => true]);
$add('Butter', null, 'oil', null, 1, 'tsp', 'tsp', 5, 35, 0, $o + ['diet' => 'dairy', 'allergens' => 'dairy', 'limited' => true, 'meal_tags' => 'breakfast,main']);

return $rows;
