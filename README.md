# FitnessWalkers: gym management app

A business + member app for one gym. The owner (who is also the trainer) has an admin area;
members get a phone-friendly app. Built with Laravel (PHP) + MySQL so it runs on ordinary shared hosting (register.lk).
No payments happen on the site: the owner records them.

* Owner area: `/admin`   * Member app: `/app`   * Sign in: `/login`
* **Sample data:** the app ships filled with placeholders. See [DUMMY_DATA.md](DUMMY_DATA.md).

## What it does

**Owner (/admin), five sections:** **Today** (items requiring attention, quick check-in, present today, month at a glance) ·
**Members** (members list with filters, member pages, Renewals, Enquiries, Workout requests, Announcements) ·
**Finances** (payments and printable receipts, expenses, financial reports) ·
**Library** (exercises, workout templates, equipment, food prices, meal plans) ·
**Settings** (gym profile, packages, check-in QR, my account, data export, activity log, sample data).

**Member (/app):** Home (check-in status, membership countdown), Workout (plan, logging, exercise guides, request a plan),
Food (rule-based meal plan with costs in Rs., shopping list), Progress (weight, BMI, measurements, goals) and Account
(membership, renewal request, announcements, password). Members check in by scanning the QR poster at the gym entrance.

**Public website:** Home, About us, Pricing, Contact us (with a membership enquiry form), Privacy policy, Terms and conditions.

## Run it on your computer

Needs PHP 8.3+, Composer.

```bash
composer install
cp .env.example .env && php artisan key:generate
# for local use, set in .env:  APP_ENV=local  APP_DEBUG=true  DB_CONNECTION=sqlite  (and remove the other DB_ lines)
touch database/database.sqlite
php artisan migrate --seed       # --seed loads the sample data
php artisan serve                # http://127.0.0.1:8000
php artisan test                 # 62 tests
```
Sample logins (password `password`): owner `owner@fitnesswalkers.test` · member `kasun@example.test` (has a plan and history),
`dilshan@example.test` (fresh member).

There is no Node/npm build: the CSS and JS are plain files in `public/`.

## Putting it online (register.lk, step by step)

1. **cPanel → MySQL Databases:** create a database and a user, give the user all privileges. Note the three names and the password.
2. **cPanel → File Manager:** next to `public_html`, create a folder `fitnesswalkers`. Inside it create a file `.env` copied from `.env.example`
   with your real domain, database name/user/password, and a generated `APP_KEY` (run `php artisan key:generate --show` on your computer).
3. **GitHub:** push this project to a (private) repository on the `main` branch.
   Repository → Settings → Secrets and variables → Actions → add: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`
   (from cPanel → FTP Accounts; use the main account so it can see both folders). Optional: `FTP_PROTOCOL` (`ftp` or `ftps`),
   `APP_DIR` (default `/fitnesswalkers/`), `PUBLIC_DIR` (default `/public_html/`).
4. Push. GitHub Actions runs the tests and, if they pass, uploads the app (outside `public_html`) and the public files (into `public_html`).
5. **Create the tables once:** if your plan has SSH, run `cd ~/fitnesswalkers && php artisan migrate --force --seed`.
   No SSH? In cPanel → **Cron Jobs** add a job for the next minute: `cd ~/fitnesswalkers && /usr/local/bin/php artisan migrate --force --seed`,
   wait for it to run, then delete the job. (After later updates that add tables, run `php artisan migrate --force` the same way.)
6. **Scheduled jobs:** cPanel → Cron Jobs → every minute: `cd ~/fitnesswalkers && /usr/local/bin/php artisan schedule:run >/dev/null 2>&1`
   (runs the weekly food-price update if you set `PRICE_FEED_URL`).
7. Sign in, go to **Settings → My account** and replace the sample login, then work through **Settings → Sample data**.
8. Make sure PHP is 8.3+ (cPanel → Select PHP Version) and that `storage/` and `bootstrap/cache/` are writable (permissions 755/775).

## Putting it online on Vercel (alternative to register.lk)

Vercel has no PHP of its own, so this uses the community `vercel-php` runtime (`vercel.json`, `api/index.php`). Things that behave differently there:
video **file uploads** for exercises are switched off (paste a video link instead), and there is no cron, so the weekly food-price update does not run.

1. **Database:** Vercel has none, so create a free MySQL database elsewhere (for example TiDB Cloud Serverless or Aiven). Note host, port, database, user and password.
2. **Create the tables once, from your computer:** put those details in your local `.env` (`DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`,
   and if the provider requires SSL, `MYSQL_ATTR_SSL_CA=` the path of your system's CA file, e.g. `/etc/ssl/certs/ca-certificates.crt` on Linux/Mac),
   then run `php artisan migrate --force --seed`. Afterwards put your `.env` back to SQLite for local work.
3. **vercel.com:** Add New > Project > import this GitHub repository. Framework preset **Other**; leave build and output settings alone (`vercel.json` sets them).
4. **Environment variables** (Project > Settings > Environment Variables): `APP_KEY` (run `php artisan key:generate --show` on your computer), `APP_URL` (your Vercel address, with https),
   `APP_TIMEZONE=Asia/Colombo`, and the same `DB_*` values (plus `MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt` if SSL is required).
5. Deploy. Vercel redeploys on every push to the branch you chose as Production Branch.

## Food prices: how "automatic" really is

There is no free, live feed of Sri Lankan food prices (the Census Dept and HARTI publish PDFs/tables, not an API). So prices update three ways:
the owner edits them in **Foods & prices** · uploads a CSV (`name,price_per_kg`) · or a JSON feed URL is set in `PRICE_FEED_URL` and
`php artisan foods:update-prices` pulls it weekly. Every automatic update refuses changes bigger than ±40%, skips prices the owner locked, and is
written to the change history. Members always see "estimate" and the date prices were last updated.

## Safety rules built into the diet generator

Under-18s, pregnancy/breastfeeding, any medical condition or injury, and a BMI under 18.5 are **refused** and sent to the owner/a doctor.
Calories never go below 1,500 (men) / 1,200 (women). Allergies and diet type (vegan / vegetarian / everything) are filtered out of every meal.
If the budget is too small, the plan says so instead of pretending. This is a rule-based generator, not AI, and is a general guide, not medical advice.

## Security notes

Passwords are hashed; members can only ever read/write their own data (tested); the owner area is role-protected; login is rate-limited;
CSV exports neutralise spreadsheet formulas; forms use CSRF tokens; `APP_DEBUG` must be `false` in production.
Take a backup (Admin → Backup & export, and cPanel → Backup) regularly.

## Where things are

`app/Http/Controllers/Admin` and `Member` (screens) · `app/Services/DietGenerator.php`, `PriceUpdater.php` · `app/Support/Dummy.php` ·
`resources/views` · `database/seeders/DummySeeder.php` · `tests/Feature` · `.github/workflows/deploy.yml`.
