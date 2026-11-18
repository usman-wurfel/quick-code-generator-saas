# Quick Code

**Quick Code** is a full-stack QR Code Generator SaaS platform for creating, managing, and tracking dynamic QR codes.

It includes a dark, tech-focused marketing frontend and a production-style admin dashboard for QR management, users, subscriptions, content, and system settings.

**Stack:** Laravel · Lit · Vite · MySQL · Sass

---

## Screenshots

### Frontend — Hero / Generator

![Frontend hero](docs/screenshots/frontend-hero.png)

### Frontend — Gradients showcase

![Frontend gradients](docs/screenshots/frontend-gradients.png)

### Frontend — QR code types

![Frontend QR types](docs/screenshots/frontend-types.png)

### Frontend — Pricing

![Frontend pricing](docs/screenshots/frontend-pricing.png)

### Frontend — Login

![Login page](docs/screenshots/frontend-login.png)

### Admin — QR Code list

![Admin QR codes](docs/screenshots/admin-qrcodes.png)

### Admin — Archived QR codes

![Admin archived QR codes](docs/screenshots/admin-qrcodes-archived.png)

### Admin — Create QR Code

![Admin create QR code](docs/screenshots/admin-qrcode-create.png)

### Admin — Users

![Admin users](docs/screenshots/admin-users.png)

### Admin — Paying users

![Admin paying users](docs/screenshots/admin-users-paying.png)

### Admin — Non-paying users

![Admin non-paying users](docs/screenshots/admin-users-non-paying.png)

### Admin — Subscription plans

![Admin plans](docs/screenshots/admin-plans.png)

### Admin — Subscriptions

![Admin subscriptions](docs/screenshots/admin-subscriptions.png)

### Admin — Transactions

![Admin transactions](docs/screenshots/admin-transactions.png)

### Admin — Payment processors

![Admin payment processors](docs/screenshots/admin-payment-processors.png)

### Admin — Payment gateways

![Admin payment gateways](docs/screenshots/admin-payment-gateways.png)

### Admin — Currencies

![Admin currencies](docs/screenshots/admin-currencies.png)

### Admin — Blog posts

![Admin blog posts](docs/screenshots/admin-blog-posts.png)

### Admin — Content blocks

![Admin content blocks](docs/screenshots/admin-content-blocks.png)

### Admin — Translations

![Admin translations](docs/screenshots/admin-translations.png)

### Admin — Custom codes

![Admin custom codes](docs/screenshots/admin-custom-codes.png)

### Admin — Pages

![Admin pages](docs/screenshots/admin-pages.png)

### Admin — Contacts

![Admin contacts](docs/screenshots/admin-contacts.png)

### Admin — Dynamic biolink blocks

![Admin biolink blocks](docs/screenshots/admin-biolink-blocks.png)

### Admin — System status

![Admin system status](docs/screenshots/admin-system-status.png)

### Admin — System settings

![Admin settings](docs/screenshots/admin-settings.png)

### Admin — Notifications

![Admin notifications](docs/screenshots/admin-notifications.png)

### Admin — SMS portals

![Admin SMS portals](docs/screenshots/admin-sms-portals.png)

### Admin — Auth workflow

![Admin auth workflow](docs/screenshots/admin-auth-workflow.png)

### Admin — Domains

![Admin domains](docs/screenshots/admin-domains.png)

---

## Features

### Marketing frontend
- Dark IT / tech visual system (hero, sections, footer)
- Live homepage QR generator with many QR types (URL, WiFi, vCard, WhatsApp, Event, Crypto, and more)
- Gradient + custom shape showcases
- Pricing plans, testimonials, blog cards
- Responsive layout

### Admin dashboard
- QR code CRUD, archive, export (SVG / PNG), share tools
- User management (all / paying / non-paying)
- Subscriptions, plans, transactions, payment processors, currencies
- Content: blog posts, content blocks, translations, custom code, pages
- System settings: general, appearance, logos, menus, QR types, storage, advanced
- Auth workflows, domains, plugins, notifications

### Platform
- Laravel 9 backend API + Blade layouts
- Lit-based dashboard SPA (shadow DOM components)
- Config-driven theme tokens
- Multi-language translation files
- Subscription / billing modules

---

## Tech stack

| Layer | Stack |
| --- | --- |
| Backend | PHP 8.2, Laravel 9, MySQL / MariaDB |
| Frontend theme | Vite 4, Sass, custom blue theme |
| Admin UI | Lit web components (dashboard bundle) |
| Auth | Laravel Sanctum |
| QR | chillerlan/php-qrcode + Imagick (optional for some exports) |

---

## Project structure

```text
app/                 Laravel application code
config/              App configuration
database/            Migrations, seeders, raw content
docs/screenshots/    Portfolio screenshots used in this README
public/              Web root (assets, dashboard JS, index)
resources/themes/blue/   Vite theme source (SCSS / JS)
resources/views/blue/    Blade layouts & sections
routes/              Web + API routes
storage/             Logs, cache, uploads
```

---

## Local setup

### Requirements
- PHP 8.2+ with common Laravel extensions
- Composer
- MySQL / MariaDB
- Node.js 18+ (only if you rebuild the theme)

### 1. Install PHP dependencies

```bash
composer install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Update DB settings in `.env`:

```env
DB_DATABASE=qr_code
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database

```bash
php artisan migrate
# seed if your dump is empty
php artisan db:seed
```

### 4. Storage link

```bash
php artisan storage:link
```

### 5. Run

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Open the app on your local server port `8000`.

### Default admin (local)

- Email: `admin@gmail.com`
- Password: `admin1`

Change these after first login for any shared environment.

---

## Theme rebuild (optional)

Only needed when editing SCSS / theme JS under `resources/themes/blue`:

```bash
cd resources/themes/blue
npm install
npm run build
node scripts/sync-blue-theme.js
```

Then copy the built hashed assets from `resources/themes/blue/dist/assets/` into `public/assets/` and update `resources/views/blue/partials/head/blue-assets.blade.php` if the hash changed.

Global overlay styles live in:

- `public/assets/forge-theme.css`

---

## Design notes

- Marketing pages use a dark ink shell (`#070b14`) with signal orange (`#ff5a1f`) and teal accents.
- Admin keeps a light workspace for form / table readability, with the orange sidebar brand color.
- Login / account screens force dark text on white cards so labels stay visible.
- Vendor marketplace identifiers are neutralized for portfolio presentation.

---

## License

Proprietary — portfolio / demonstration use. Not for redistribution as a commercial template.
