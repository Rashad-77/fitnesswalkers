# Dummy (placeholder) data register

Everything below was invented so the app could be built and tested. **None of it is real.**
It is all created by `database/seeders/DummySeeder.php` and every row has `is_dummy = true`.

## How you find and replace it

| What | How |
|---|---|
| See what is left | Admin → Settings → **Sample data** (or `php artisan dummy:list`) |
| Replace one item | Open it in the admin and **Save** — saving a sample row makes it real |
| Delete all of it at once | Admin → Settings → Sample data → *Remove sample data* (type DELETE), or `php artisan dummy:purge` |
| Spot it in the app | Yellow badge **sample** / yellow edge on table rows; the yellow bar at the top of every admin page |

Safety: the "remove all sample data" button refuses to run until you have changed the sample owner login
(Admin → Settings → My account), so you can't lock yourself out.

## The list

| # | Data | Where it lives | Replace with | Notes |
|---|---|---|---|---|
| 1 | **Owner login** `owner@fitnesswalkers.test` / `password` | users | Admin → Settings → My account | **Change before going live.** |
| 2 | 12 sample members (Kasun Perera, Nimali Fernando …) with emails `…@example.test`, password `password`, phones `07700000xx` | users, members | Real members (Admin → Members) | Delete the samples when you've added real ones. |
| 3 | Their memberships, 48 payments, receipts `R-2026-…` | memberships, payments | Real records | Dates are relative to the day it was seeded. |
| 4 | Gym phone `077 000 0000`, address, opening hours, email, tagline, About us text, bank details, receipt footer, owner name | settings | Admin → Settings | The gym name **FitnessWalkers is real**, not a placeholder. These also appear on the public Home, About, Contact and Pricing pages. |
| 4b | Privacy policy and Terms and conditions text | `resources/views/pages/privacy.blade.php`, `terms.blade.php` | Edit the files | **Draft templates.** Have the owner (ideally a lawyer) review them before going live, then delete the yellow "Draft template" box. |
| 5 | Packages Monthly **Rs. 5,000**, Quarterly **Rs. 13,500**, Annual **Rs. 48,000** | plans | Admin → Settings → Packages | Prices and lengths are guesses. |
| 6 | 30 expenses (Rent 60,000, Electricity 18,000, Salaries 25,000 …) | expenses | Real expenses | Only there to make the finance charts show something. |
| 7 | 2 announcements | announcements | Your own | |
| 8 | 14 equipment items (Leg Press, Lat Pulldown, Treadmill …; **Leg Curl marked out of order** as a demo) | equipment | Admin → Equipment | Make the list match what your gym really has. |
| 9 | 23 exercises with steps + common mistakes (written generically) | exercises | Admin → Exercise library | **No video clips exist yet** — every exercise shows "demo clip coming soon". Upload a short MP4/WebM per exercise. |
| 10 | 3 workout templates (Beginner Full Body, Fat Loss Circuit, Muscle Gain Upper/Lower) | workout_templates | Your own programmes | |
| 11 | 1 approved plan, 1 pending plan request, 54 workout logs, 10 body measurements, 1 goal | workout_plans, plan_requests, workout_logs, body_logs, goals | — (members create their own) | Belong to sample members. |
| 12 | 35 foods with calories/protein/carbs/fat and **Rs. per kg prices** | foods | Admin → Foods & prices | Nutrition values are typical figures; **all prices are rough guesses**. Check each one. |
| 13 | 2 diet plans (one app-made, one owner-written) | diet_plans | — | Belong to sample members. |
| 14 | 1 renewal request | renewal_requests | — | |
| 15 | 21 sample visits (check-ins) for 8 members, so "Last visit" and "Here today" show something. Two members have none, to demo "Low attendance" | attendances | — | Real visits come from the door QR or the owner's Check in button. |
| 16 | 4 sample enquiries (Amaya Wijesinghe, Nuwan Herath, Piumi Karunaratne, Janaka Peiris) | enquiries | — | Real ones arrive from the website form or Add enquiry. |
| 17 | Sample health-form answers and signed-terms dates on all 12 members (one has a "yes" answer, one has no signed terms) | members | — | Real members get their own form when added. |

## Not in the database but also placeholder

- `public/img/logo-banner.png` and the app icons are cut from the screenshot of the logo you sent. Replace them with clean files (same names).
- `.env.example` has `your-domain.lk` and empty database details.
- There is **no live food-price feed**: prices only change when the owner edits them, uploads a CSV, or a `PRICE_FEED_URL` is set.
