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

## 5. Image Processing & Optimization
The system includes an automatic optimization engine (`includes/helpers.php`):
- **Auto-resize:** Downscales images larger than 1200px to maintain performance.
- **Alpha-Preserve:** Keeps transparency for PNG frames.
- **Compression:** Applies 85% quality to maintain "premium" looks with "lightweight" payloads.

## 6. How to Extend
- **New Tables:** Add the table definition to `database/schema.sql` and register it in `DBManager::get_required_tables()`.
- **New Material Types:** Update `inventory.php` `$validTypes` and the DB `materials` table.
- **UI Tweaks:** The design system is controlled via `index.css` and Tailwind utility classes (standardized to `gray-500` for text legibility).
