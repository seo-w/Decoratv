# DecoraTV - Technical Architecture Guide

This document provides essential context for any developer or AI tasked with maintaining or extending the DecoraTV platform.

## 1. Project Philosophy
DecoraTV is a professional, high-performance web application designed for flexibility across hosting environments. It follows a modular architecture that supports both **SQLite 3** (local/simple) and **MySQL/MariaDB** (scalable/robust) through a unified data bridge using **PHP 8+**.

Key focuses:
- **Visual-First:** Minimalistic UI that prioritizes product visualization and premium aesthetics.
- **Resilience:** All interactions (quotes) are persisted in a local DB before any network action.
- **Simplicity:** No complex build steps (No Node/React), using only Vanilla JS for maximum speed and compatibility.
- **Accessibility:** WCAG-aligned contrast ratios for professional visual clarity.

## 2. Directory Structure
```text
/
├── admin/              # Management panel (Dashboard, Inventory, Inquiries, Settings)
├── config/             # DB connection (PDO) and Central Config
├── database/           # SQLite file (Protected by .htaccess) and schema.sql
├── includes/           # Shared logic (Auth, Image Helpers, DB Manager)
├── public/             # Entry point for the Simulator
│   ├── api/            # API endpoints (Quote capture)
│   ├── assets/         # Static global assets
│   ├── uploads/        # Optimized material images
│   └── index.php       # The Studio Simulator
└── TECHNICAL_GUIDE.md  # This document
```

## 3. Data Architecture (Hybrid Engine)
The system uses a specialized `DBManager` class (`includes/db_manager.php`) to abstract engine differences:
- **Engine Switching:** Defined via `DB_DRIVER` in `config/config.php`.
- **Auto-Initialization:** Detects missing tables and restores the base schema dynamically during repairs.
- **Data Migration:** Full engine-to-engine transfer support implemented for transitioning from SQLite to MySQL.

### Data Model
- **`materials`**: Frames, liners, and artworks with internal tracking IDs and grouped categorization.
- **`quotes`**: Permanent archive including customer PII and full design state serialized as JSON.
- **`settings`**: Persistent system state (SMTP configurations, admin contact emails).
- **`users`**: Secure admin credentials using `password_hash`.

## 4. Visualizer Logic (The Z-Stack)
The simulator uses a 3-layer absolute positioning system:
1. **Liner Layer (z-0):** The mounting base. Scaled at **85%** of the canvas.
2. **Art Layer (z-10):** The artwork. Features an **Adaptive Logic**:
   - If a Liner is present: Art scales to **80%** (leaving a border).
   - If NO Liner: Art scales to **86%** (filling the gap).
3. **Frame Layer (z-20):** A PNG/JPG mask at **100%** size that encases everything else.

## 5. Selection Tracking (Real-time IDs)
The simulator implements a real-time feedback loop for material identification:
- **Elements:** `display-frame-id`, `display-liner-id`, `display-art-id`.
- **Logic:** The `updateUI()` function syncs these DOM elements with `state.{item}.internal_id`.
- **UX:** IDs are styled with color-coded tracking (Amber/Blue/Emerald) to provide immediate visual confirmation of the active selection.

## 6. Image Processing & Optimization
The system includes an automatic optimization engine (`includes/helpers.php`):
- **Auto-resize:** Downscales images larger than 1200px to maintain performance.
- **Alpha-Preserve:** Keeps transparency for PNG frames.
- **Compression:** Applies 85% quality to maintain "premium" looks with "lightweight" payloads.

## 7. Notification System & SMTP
DecoraTV uses a professional-grade mailing system to ensure quote delivery:
- **Engine:** Integrated **PHPMailer** for authenticated SMTP.
- **Security:** Supports TLS/SSL and App Passwords for modern providers like Gmail.
- **Reliability:** Every outgoing email is logged in the `quotes` table under `mail_sent`.
- **Diagnostics:** The Admin Settings includes a "Test Connection" tool (`api/test_mail.php`) to verify connectivity.

## 8. Dashboard Features
- **Inquiries Tracking:** The `admin/inquiries.php` view displays a "Sent/Fail" status badge for every notification email, providing a fallback for the administrator if an email is not received.
- **Centralized Settings:** The `admin/settings.php` panel allows real-time updates to SMTP credentials and receiver addresses without editing config files.

## 9. Visual Experience Design
- **Lightbox:** Implemented in `index.php` using Flexbox for perfect centering.
- **Branding:** Background color set to `#cfc1b4` with high-contrast dark typography (`gray-900/40`) to maintain a premium feel.

## 10. How to Extend
- **New Tables:** Add the table definition to `database/schema.sql` and register it in `DBManager::get_required_tables()`.
- **New Material Types:** Update `inventory.php` `$validTypes` and the DB `materials` table.
- **UI Tweaks:** The design system is controlled via `index.css` and Tailwind utility classes.
- **Documentation:** Keep `proyecto_decoratv.md` (Spanish) and `TECHNICAL_GUIDE.md` (English) in sync for all major feature additions.
