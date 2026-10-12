<?php

/**
 * The dish library the meal planner draws from: everyday Sri Lankan dishes made from the foods in foods.php.
 * Each dish is one normal portion. The planner makes the main course and the protein bigger or smaller to fit the target;
 * vegetables, coconut and oil stay as they are.
 *
 * Items: [food name, servings, code]  m = the main course (scales), s = a protein dish (scales), f = fixed, d = dessert (scales).
 * Amounts are household servings of that food, so "3" of rice is 1 1/2 cups cooked. Calories are worked out from the foods.
 * These are sensible everyday dishes, not recipes from a cookbook. The owner or a dietitian should review them in Library > Dishes.
 */
$rows = [];
$dish = function (string $name, ?string $local, string $kind, string $slots, string $tags, int $mins, string $method, array $items) use (&$rows) {
    $rows[] = compact('name', 'local', 'kind', 'slots', 'tags', 'mins', 'method', 'items');
};

$RR = 'Red rice (kekulu), cooked'; $WR = 'White rice, cooked'; $FM = 'Finger millet (kurakkan), cooked'; $PM = 'Proso millet (meneri), cooked';
$FISH = 'Fish, fresh (any local fish, e.g. tuna, paraw, mullet)'; $DHAL = 'Dhal, cooked'; $CO = 'Coconut oil'; $CM = 'Coconut milk'; $SC = 'Scraped coconut';
$STR = 'String hoppers (rice flour)'; $ROTI = 'Roti, whole wheat (atta)'; $GOTU = 'Gotukola (green leaves)';

/* ---------------- breakfast ---------------- */
$dish('String hoppers with dhal and pol sambol', 'Idi appa, parippu, pol sambol', 'meal', 'breakfast', 'quick', 20, 'Steam the string hoppers. Warm the dhal with a little coconut milk and turmeric. Mix scraped coconut with chilli, onion and lime for the sambol.',
    [[$STR, 3, 'm'], [$DHAL, 2, 's'], [$SC, 2, 'f'], ['Tomato', 1, 'f']]);
$dish('String hoppers with egg curry', 'Idi appa, biththara hodi', 'meal', 'breakfast', '', 30, 'Boil the eggs and simmer them in a light coconut milk gravy with onion, curry leaves and turmeric. Serve with steamed string hoppers.',
    [[$STR, 3, 'm'], ['Egg, hen', 2, 's'], [$CM, 1, 'f'], [$CO, 1, 'f'], ['Pumpkin', 1, 'f']]);
$dish('Hoppers with egg', 'Appa saha biththara', 'meal', 'breakfast', '', 30, 'Cook the hoppers in a hot hopper pan. Break an egg into the middle of two of them as they cook. Serve with scraped coconut and chilli.',
    [['Hoppers', 5, 'm'], ['Egg, hen', 2, 's'], [$SC, 1, 'f']]);
$dish('Pittu with green gram and coconut milk', 'Pittu, mung ata, pol kiri', 'meal', 'breakfast', '', 25, 'Steam the pittu. Boil the green gram until soft. Serve with a little warm coconut milk.',
    [['Pittu (rice flour)', 3, 'm'], ['Green gram (mung), boiled', 2, 's'], [$CM, 2, 'f']]);
$dish('Roti with dhal curry', 'Roti saha parippu', 'meal', 'breakfast', 'quick', 25, 'Make the whole wheat roti on a hot pan. Cook the dhal with onion, curry leaves and turmeric and finish with a spoon of coconut oil.',
    [[$ROTI, 3, 'm'], [$DHAL, 3, 's'], [$CO, 1, 'f'], [$GOTU, 1, 'f']]);
$dish('Roti with egg and pol sambol', 'Roti, biththara, pol sambol', 'meal', 'breakfast', 'quick', 20, 'Make the roti. Scramble or boil the eggs. Mix scraped coconut with chilli, onion and lime.',
    [[$ROTI, 3, 'm'], ['Egg, hen', 2, 's'], [$SC, 2, 'f']]);
$dish('Wholemeal bread with egg and gotukola sambol', null, 'meal', 'breakfast', 'quick', 10, 'Toast the bread. Scramble the eggs. Chop gotukola with onion, coconut and lime for the sambol.',
    [['Bread, whole wheat', 4, 'm'], ['Egg, hen', 2, 's'], [$SC, 1, 'f'], [$GOTU, 1, 'f']]);
$dish('Oats porridge with banana and cashew', null, 'meal', 'breakfast', 'quick', 10, 'Cook the oats in water or milk. Top with sliced banana and chopped cashew nuts.',
    [['Oats, rolled or steel-cut, cooked', 3, 'm'], ['Fresh milk', 1, 'f'], ['Banana', 1, 'f'], ['Cashew nuts', 1, 'f']]);
$dish('Kola kanda with kurakkan', 'Kola kenda', 'meal', 'breakfast', 'quick', 20, 'Cook the finger millet with a handful of gotukola, then stir in coconut milk. Serve with boiled green gram.',
    [[$FM, 3, 'm'], [$GOTU, 1, 'f'], [$SC, 2, 'f'], ['Green gram (mung), boiled', 1, 's']]);
$dish('Dosai with dhal and tomato', null, 'meal', 'breakfast', '', 30, 'Make the dosai on a hot pan. Cook the dhal with tomato and spices.',
    [['Dosai', 4, 'm'], [$DHAL, 3, 's'], ['Tomato', 1, 'f'], [$SC, 1, 'f']]);
$dish('Sweet potato with egg and coconut', null, 'meal', 'breakfast', 'quick', 25, 'Boil the sweet potato. Boil the eggs. Serve with scraped coconut and a pinch of salt.',
    [['Sweet potato, boiled', 3, 'm'], ['Egg, hen', 2, 's'], [$SC, 2, 'f']]);
$dish('Curd, oats and fruit bowl', null, 'meal', 'breakfast', 'quick', 5, 'Stir the oats and raisins into the curd and top with banana.',
    [['Oats, rolled or steel-cut, cooked', 2, 'm'], ['Curd', 2, 's'], ['Banana', 1, 'f'], ['Raisins', 1, 'f']]);

/* ---------------- lunch and dinner ---------------- */
$dish('Red rice with dhal, pumpkin curry and gotukola sambol', 'Kekulu, parippu, wattakka, gotukola sambol', 'meal', 'lunch,dinner', 'quick', 40, 'Cook the dhal and pumpkin separately with coconut milk, turmeric and curry leaves. Chop gotukola with onion, coconut and lime.',
    [[$RR, 3, 'm'], [$DHAL, 3, 's'], ['Pumpkin', 2, 'f'], [$GOTU, 1, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with fish ambul thiyal, mukunuwenna mallum and dhal', 'Bath, maalu ambul thiyal, mukunuwenna mallum, parippu', 'meal', 'lunch,dinner', '', 45, 'Cook the fish in goraka and pepper until dry. Stir-fry the mukunuwenna with scraped coconut. Cook the dhal separately.',
    [[$WR, 3, 'm'], [$FISH, 2, 's'], [$DHAL, 2, 's'], ['Mukunuwenna (green leaves)', 2, 'f'], [$SC, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with chicken curry, beans and dhal', 'Bath, kukul mas, bonchi, parippu', 'meal', 'lunch,dinner', '', 45, 'Simmer the chicken with curry powder, onion and coconut milk. Cook the beans with scraped coconut. Cook the dhal separately.',
    [[$RR, 3, 'm'], ['Chicken, skinless', 3, 's'], ['Beans', 2, 'f'], [$DHAL, 2, 's'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with egg curry and brinjal', 'Bath, biththara hodi, wambatu', 'meal', 'lunch,dinner', 'quick', 35, 'Boil the eggs and simmer in a coconut milk gravy. Cook the brinjal with onion and spices.',
    [[$WR, 3, 'm'], ['Egg, hen', 2, 's'], ['Brinjal', 2, 'f'], [$GOTU, 1, 'f'], [$CM, 2, 'f'], [$CO, 1, 'f']]);
$dish('Rice with chickpea curry and spinach mallum', 'Bath, kadala, nivithi mallum', 'meal', 'lunch,dinner', 'batch', 40, 'Boil the chickpeas and simmer with coconut milk and spices. Stir-fry the spinach with scraped coconut.',
    [[$RR, 3, 'm'], ['Chickpea (kadala), boiled', 2, 's'], ['Spinach (green leaves)', 2, 'f'], ['Carrot', 1, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with soya meat curry and long beans', 'Bath, soya, me karal', 'meal', 'lunch,dinner', 'quick', 35, 'Cook the soya with onion, curry powder and coconut milk. Cook the long beans with scraped coconut.',
    [[$RR, 3, 'm'], ['Soya beans, boiled', 2, 's'], ['Long beans', 2, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with prawn curry, dhal and drumstick leaves', 'Bath, issa curry, parippu, murunga kola', 'meal', 'lunch,dinner', '', 40, 'Simmer the prawns with tomato, onion and coconut milk. Cook the drumstick leaves with scraped coconut. Cook the dhal separately.',
    [[$WR, 3, 'm'], ['Prawns', 3, 's'], [$DHAL, 2, 's'], ['Drumstick leaves (green leaves)', 2, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with dried sprats, dhal and pol sambol', 'Bath, haal masso, parippu, pol sambol', 'meal', 'lunch,dinner', 'quick', 30, 'Fry the sprats with onion and chilli. Cook the dhal separately. Serve with pol sambol and sliced beetroot.',
    [[$RR, 3, 'm'], ['Dried sprats', 2, 's'], [$DHAL, 2, 's'], ['Tomato', 1, 'f'], [$SC, 2, 'f'], ['Beetroot', 1, 'f']]);
$dish('Rice with tuna curry, green beans and mung parippu', 'Bath, kelawalla hodi, bonchi, mung parippu', 'meal', 'lunch,dinner', '', 45, 'Simmer the tuna with goraka, spices and coconut milk. Cook the beans and mung parippu separately.',
    [[$RR, 3, 'm'], ['Tuna (kelawalla)', 3, 's'], ['Beans', 2, 'f'], ['Mung parippu (split green gram), cooked', 2, 's'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with beef curry, long beans and gotukola', 'Bath, harak mas, me karal', 'meal', 'lunch,dinner', 'weekend', 75, 'Slow-cook the lean beef with spices until tender. Cook the long beans with scraped coconut.',
    [[$WR, 3, 'm'], ['Beef, lean', 3, 's'], ['Long beans', 2, 'f'], [$GOTU, 1, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with cowpea curry and pumpkin leaves mallum', null, 'meal', 'lunch,dinner', 'batch', 40, 'Boil the cowpeas and simmer with coconut milk and spices. Stir-fry the pumpkin leaves with scraped coconut.',
    [[$RR, 3, 'm'], ['Cowpea, boiled', 2, 's'], ['Pumpkin leaves (green leaves)', 2, 'f'], ['Pumpkin', 1, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with mung bean curry and snake gourd', 'Bath, mung ata, pathola', 'meal', 'lunch,dinner', 'quick', 35, 'Boil the mung beans and simmer with coconut milk. Cook the snake gourd with onion and spices.',
    [[$RR, 3, 'm'], ['Green gram (mung), boiled', 2, 's'], ['Snake gourd', 2, 'f'], [$GOTU, 1, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Finger millet with dhal and gotukola sambol', 'Kurakkan, parippu, gotukola sambol', 'meal', 'dinner', 'quick', 30, 'Cook the millet like rice. Cook the dhal separately and serve with gotukola sambol.',
    [[$FM, 3, 'm'], [$DHAL, 3, 's'], [$SC, 2, 'f'], [$GOTU, 1, 'f']]);
$dish('Roti with chicken curry', 'Roti, kukul mas', 'meal', 'dinner', '', 40, 'Make the roti. Simmer the chicken with tomato, spices and coconut milk.',
    [[$ROTI, 4, 'm'], ['Chicken, skinless', 3, 's'], ['Tomato', 1, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Roti with fish curry and dhal', 'Roti, maalu hodi, parippu', 'meal', 'dinner', '', 40, 'Make the roti. Simmer the fish with goraka and coconut milk. Cook the dhal separately.',
    [[$ROTI, 4, 'm'], [$FISH, 2, 's'], [$DHAL, 2, 's'], [$GOTU, 1, 'f'], [$CM, 1, 'f']]);
$dish('String hoppers with chicken curry and pol sambol', 'Idi appa, kukul mas, pol sambol', 'meal', 'dinner', '', 40, 'Steam the string hoppers. Simmer the chicken with spices and coconut milk. Mix scraped coconut with chilli and lime.',
    [[$STR, 4, 'm'], ['Chicken, skinless', 3, 's'], [$SC, 2, 'f'], ['Beans', 1, 'f']]);
$dish('Hoppers with egg and fish curry', 'Appa, biththara, maalu hodi', 'meal', 'dinner', '', 40, 'Cook the hoppers, breaking an egg into two of them. Serve with a small fish curry.',
    [['Hoppers', 5, 'm'], ['Egg, hen', 2, 's'], [$FISH, 1, 's'], [$SC, 2, 'f']]);
$dish('Vegetable rice with egg and cashew', null, 'meal', 'lunch,dinner', 'quick', 25, 'Fry the rice with carrot, beans and egg. Top with toasted cashew nuts.',
    [[$WR, 3, 'm'], ['Egg, hen', 2, 's'], ['Carrot', 2, 'f'], ['Beans', 2, 'f'], ['Cashew nuts', 1, 'f'], [$CO, 1, 'f']]);
$dish('Millet with fish curry and kankun mallum', 'Meneri, maalu hodi, kankun mallum', 'meal', 'lunch,dinner', '', 45, 'Cook the millet like rice. Simmer the fish with spices. Stir-fry the kankun with scraped coconut. Cook the dhal separately.',
    [[$PM, 3, 'm'], [$FISH, 2, 's'], ['Kankun (green leaves)', 2, 'f'], [$DHAL, 2, 's'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with horse gram curry and beetroot', 'Bath, kollu, beetroot', 'meal', 'lunch,dinner', 'batch', 45, 'Boil the horse gram and simmer with spices. Cook the beetroot with coconut.',
    [[$RR, 3, 'm'], ['Horse gram (kollu), boiled', 2, 's'], ['Beetroot', 2, 'f'], ['Kankun (green leaves)', 1, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with black gram curry and brinjal', 'Bath, ulundu, wambatu', 'meal', 'lunch,dinner', 'batch', 40, 'Boil the black gram and simmer with spices and coconut milk. Cook the brinjal separately.',
    [[$RR, 3, 'm'], ['Black gram (ulundu), boiled', 2, 's'], ['Brinjal', 2, 'f'], [$GOTU, 1, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Breadfruit curry with dhal and gotukola sambol', 'Del curry, parippu', 'meal', 'lunch,dinner', 'weekend', 50, 'Boil the breadfruit and simmer with coconut milk and spices. Cook the dhal separately.',
    [['Breadfruit, boiled', 3, 'm'], [$DHAL, 3, 's'], [$GOTU, 1, 'f'], [$CM, 2, 'f']]);
$dish('Red rice with jackfruit curry and dhal', 'Kekulu, polos, parippu', 'meal', 'lunch,dinner', 'weekend', 60, 'Simmer the young jackfruit with spices and coconut milk until soft. Cook the dhal separately.',
    [[$RR, 2, 'm'], ['Jackfruit, boiled or cooked', 3, 'f'], [$DHAL, 2, 's'], [$CM, 2, 'f'], [$CO, 1, 'f']]);
$dish('Rice with cuttlefish curry, beans and dhal', 'Bath, dallo, bonchi, parippu', 'meal', 'lunch,dinner', '', 45, 'Simmer the cuttlefish with spices. Cook the beans and dhal separately.',
    [[$WR, 3, 'm'], ['Cuttlefish', 3, 's'], [$DHAL, 2, 's'], ['Beans', 2, 'f'], [$CM, 1, 'f'], [$CO, 1, 'f']]);
$dish('Rice with mutton curry and pumpkin', 'Bath, elumasu, wattakka', 'meal', 'lunch,dinner', 'weekend', 80, 'Slow-cook the lean mutton with spices. Cook the pumpkin with a little coconut milk.',
    [[$WR, 3, 'm'], ['Mutton, lean', 3, 's'], ['Pumpkin', 2, 'f'], [$GOTU, 1, 'f'], [$CM, 1, 'f']]);
$dish('Rice with dhal, knol-khol and egg', null, 'meal', 'lunch,dinner', 'quick', 35, 'Cook the dhal. Stir-fry the knol-khol with onion and spices. Boil the eggs and serve with a little sambol.',
    [[$WR, 3, 'm'], [$DHAL, 3, 's'], ['Knol-khol', 2, 'f'], ['Egg, hen', 1, 's'], [$CO, 1, 'f']]);
$dish('Potato and egg curry with roti', null, 'meal', 'dinner', 'quick', 35, 'Boil the potato and eggs. Simmer in a light gravy with onion and spices. Serve with roti.',
    [[$ROTI, 3, 'm'], ['Potato, boiled', 1, 'f'], ['Egg, hen', 2, 's'], ['Tomato', 1, 'f'], [$CO, 1, 'f']]);

/* ---------------- snacks ---------------- */
$dish('Banana and curd', null, 'snack', 'snack', 'quick', 2, 'Slice the banana over the curd.', [['Banana', 1, 'm'], ['Curd', 1, 's']]);
$dish('Boiled chickpeas with coconut', 'Kadala', 'snack', 'snack', 'quick', 10, 'Boil the chickpeas, then toss with scraped coconut, onion and salt.', [['Chickpea (kadala), boiled', 1, 'm'], [$SC, 1, 'f']]);
$dish('Peanuts and an apple', null, 'snack', 'snack', 'quick', 2, 'A handful of peanuts with a small apple.', [['Peanuts', 2, 's'], ['Apple', 1, 'm']]);
$dish('Green gram salad', 'Mung ata salad', 'snack', 'snack', 'quick', 10, 'Mix boiled green gram with tomato, cucumber, scraped coconut and lime.', [['Green gram (mung), boiled', 1, 'm'], ['Tomato', 1, 'f'], ['Cucumber', 1, 'f'], [$SC, 1, 'f']]);
$dish('Boiled corn and peanuts', null, 'snack', 'snack', 'quick', 15, 'Boil the corn on the cob and eat with a few peanuts.', [['Corn on the cob, boiled', 1, 'm'], ['Peanuts', 1, 's']]);
$dish('Yoghurt with papaya and pumpkin seeds', null, 'snack', 'snack', 'quick', 3, 'Top the yoghurt with papaya cubes and pumpkin seeds.', [['Papaya', 1, 'm'], ['Yoghurt, unsweetened', 1, 's'], ['Pumpkin seeds', 1, 'f']]);
$dish('Boiled sweet potato with coconut', 'Bathala', 'snack', 'snack', 'quick', 20, 'Boil the sweet potato and eat with scraped coconut.', [['Sweet potato, boiled', 2, 'm'], [$SC, 1, 'f']]);
$dish('Boiled egg and cucumber', null, 'snack', 'snack', 'quick', 10, 'Boil the eggs and slice the cucumber.', [['Egg, hen', 2, 's'], ['Cucumber', 2, 'f']]);
$dish('Milk and dates', null, 'snack', 'snack', 'quick', 2, 'A glass of fresh milk with two dates.', [['Fresh milk', 1, 's'], ['Dates', 1, 'm']]);
$dish('Cashew nuts and an orange', null, 'snack', 'snack', 'quick', 2, 'A few cashew nuts with an orange.', [['Cashew nuts', 2, 's'], ['Orange', 1, 'm']]);

/* ---------------- dessert (fruit and curd) ---------------- */
foreach ([['Banana', 'Banana'], ['Papaya', 'Papaya'], ['Pineapple', 'Pineapple slices'], ['Watermelon', 'Watermelon'], ['Mango', 'Mango'], ['Guava', 'Guava'], ['Orange', 'Orange'], ['Pomegranate', 'Pomegranate']] as [$food, $label]) {
    $dish($label, null, 'dessert', 'lunch,dinner', 'quick', 2, 'Wash, peel and slice.', [[$food, 1, 'd']]);
}
$dish('Curd with a little banana', null, 'dessert', 'lunch,dinner', 'quick', 2, 'Spoon the curd into a bowl and slice the banana over it.', [['Curd', 1, 'd'], ['Banana', 1, 'f']]);

return $rows;
