# Quick Code

**Quick Code** is a full-stack QR Code Generator SaaS platform for creating, managing, and tracking dynamic QR codes.

It includes a dark, tech-focused marketing frontend and a production-style admin dashboard for QR management, users, subscriptions, content, and system settings.

**Stack:** Laravel · Lit · Vite · MySQL · Sass

---

## Screenshots

### Frontend — Hero / Generator

![Frontend hero](docs/screenshots/frontend-hero.png)

### Frontend — Full homepage

![Frontend full page](docs/screenshots/frontend-full.png)

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

### Admin — Users

![Admin users](docs/screenshots/admin-users.png)

### Admin — System settings

![Admin settings](docs/screenshots/admin-settings.png)

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
