# Amani Escrow Platform v2.0
## Africa's Most Trusted Escrow Service

### Dark Fintech Design | PWA Enabled | Fully Admin Managed

---

## Quick Start (cPanel)

1. **Upload** contents to `public_html/`
2. **Create Database** in cPanel → MySQL → Import `sql/database.sql`
3. **Edit** `config/config.php` with your DB credentials and domain URL
4. **Set Permissions**: `chmod 755 uploads/ uploads/kyc/ uploads/attachments/ uploads/site/`
5. **Login**: `admin@amaniescrow.com` / `Admin@123`

---

## What's New in v2.0

- **Dark fintech UI** matching premium crypto/fintech design standards
- **PWA support** with install prompts, service worker, admin-configurable icons
- **Admin Media Manager** - upload/replace/delete ALL site images from admin panel
- **Admin-managed branding** - logo, favicon, PWA icons all uploadable from admin
- **Admin-managed landing page** - all text, images, stats editable from settings
- **Stroke-only SVG icons** (no background fills) for clean modern look
- **Dynamic manifest.json** generated from admin settings
- **Mobile-first responsive** design throughout

## Admin Features

### Site Media Manager (`Admin → Site Media`)
- Upload/replace/delete: site logo, light logo, favicon, PWA icons (192 & 512)
- Manage all landing page images: hero, about, features, trust badges
- Every image on the platform is admin-editable

### System Settings (`Admin → System Settings`)
- **General**: Platform name, tagline, contact info
- **Branding**: Colors (accent, dark bg, card bg)
- **Fees**: Escrow fee %, min/max amounts, withdrawal fees
- **PWA**: App name, icons, theme/background colors, enable/disable
- **Homepage**: All hero text, button labels, stats numbers, about section
- **System**: Maintenance mode, registration toggle, KYC settings

---

## Tech Stack
- PHP 7.4+ (core, no frameworks)
- MySQL 5.7+ / MariaDB
- TailwindCSS (CDN) | Chart.js (CDN)
- Vanilla JavaScript | Service Worker
- Google Fonts (Outfit)

## File Structure (54 files)
```
escrow/
├── index.php              # Dark landing page
├── manifest.json          # Static PWA manifest
├── manifest-dynamic.php   # Dynamic manifest from admin settings
├── sw.js                  # Service worker for PWA
├── .htaccess              # Security + manifest rewrite
├── config/config.php
├── classes/               # Database, Auth, Escrow, Settings
├── includes/init.php      # Bootstrap + helpers
├── templates/             # Dark header, footer, 403, maintenance
├── api/                   # Notifications, messages, attachments, media upload
├── pages/
│   ├── auth/              # Login, register, logout, forgot
│   ├── dashboard/         # User dashboard, notifications
│   ├── escrow/            # List, create, view (full actions)
│   ├── wallet/            # Balance, deposits, withdrawals
│   ├── disputes/          # List, create disputes
│   ├── messages/          # Conversations
│   ├── kyc/               # Document upload
│   ├── profile/           # Edit profile, password
│   ├── settings/          # User settings
│   ├── reports/           # User analytics
│   ├── agent/             # Agent dashboard
│   └── admin/             # Full admin panel (12 pages)
│       ├── dashboard.php, users.php, agents.php
│       ├── escrows.php, disputes.php, payments.php
│       ├── kyc.php, gateways.php, settings.php
│       ├── reports.php, audit.php, media.php ← NEW
├── sql/database.sql       # Complete schema (20 tables)
└── uploads/               # kyc/, attachments/, site/
```

## Security
- BCrypt (cost 12) | CSRF on all forms | PDO prepared statements
- Session hardening | Role-based access | Activity logging
- .htaccess blocks direct access to config/classes/includes/sql
