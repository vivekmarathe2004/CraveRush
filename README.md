# CraveRush

CraveRush is a full-stack food ordering platform built with PHP + MySQL. It ships with pan-India demo data so the UI feels nationwide from the first run. This README walks through every setup step with explanations.

## Table of Contents
- Overview
- Features
- Tech Stack
- Project Structure
- Prerequisites (What You Need and Why)
- Step-by-Step Setup (Windows + XAMPP)
- Database Import (CLI and phpMyAdmin)
- Configuration Explained
- Running the App
- Demo Credentials
- Key Pages and Flows
- Seed Data Explained
- Troubleshooting Guide
- Security Notes
- Contributing
- License

## Overview
CraveRush provides:
- A customer-facing experience for browsing, ordering, and tracking food
- An admin dashboard for managing restaurants, menu items, and orders

## Features
- Restaurant discovery with search, filters, and curated sections
- Menu browsing, cart, checkout, and order tracking
- Customer profiles, addresses, payments, favorites, and notifications
- Admin tools for restaurants, menu items, orders, and reports

## Tech Stack
- PHP (server-side rendering and business logic)
- MySQL (data storage)
- HTML, CSS, JavaScript (UI and interactivity)
- Apache (web server, via XAMPP)

## Project Structure
- `index.php` - Home and discovery
- `restaurants.php` - Restaurant listing and filters
- `restaurant.php` - Restaurant menu and ordering
- `customer/` - Customer flows (cart, orders, tracking)
- `admin/` - Admin dashboard and management
- `assets/` - Styles, scripts, and images
- `config/` - Database and bootstrap
- `includes/` - Shared UI and helpers

## Prerequisites (What You Need and Why)
1. **PHP 7.4+**
   - Runs the server-side code.
2. **MySQL 5.7+ or MariaDB**
   - Stores users, restaurants, orders, etc.
3. **Apache web server**
   - Serves the PHP pages in the browser.
4. **XAMPP (recommended)**
   - Bundles Apache + MySQL + PHP and is easy to set up on Windows.

## Step-by-Step Setup (Windows + XAMPP)
1. **Install XAMPP**
   - Download XAMPP and install it.
   - Why: it includes Apache and MySQL in one package.

2. **Start Apache and MySQL**
   - Open the XAMPP Control Panel.
   - Click `Start` for `Apache` and `MySQL`.
   - Why: Apache serves the website and MySQL powers the database.

3. **Place the Project in the Web Root**
   - Copy this folder into: `C:\xampp\htdocs\`
   - Example path: `C:\xampp\htdocs\Online Food\`
   - Why: Apache serves files from `htdocs`.

4. **Verify Apache Works**
   - Open your browser and visit:
     - `http://localhost/`
   - If you see the XAMPP welcome page, Apache is working.

## Database Import
You must import the database so the app has tables and demo data.

### Option A: CLI Import (Recommended)
1. Open a terminal in the project folder.
2. Run:
   ```bash
   mysql -u root -p < config/database.sql
   ```
3. Enter your MySQL root password when asked.
4. This command:
   - Creates the database (`food_system`)
   - Creates all tables
   - Inserts demo data (users, restaurants, menu items)

### Option B: phpMyAdmin Import
1. Open: `http://localhost/phpmyadmin/`
2. Click **New** and create a database named `food_system`.
3. Select the new database, then click the **Import** tab.
4. Choose `config/database.sql` and click **Go**.
5. This imports schema and demo data just like the CLI method.

## Configuration Explained
Configuration lives in:
- `config/db.php`

You may need to update these values:
- **Host**: usually `localhost`
- **Database**: `food_system`
- **User**: often `root`
- **Password**: whatever your MySQL root password is

If you change the database name in one place, change it in both:
- `config/database.sql` (the SQL creates the database)
- `config/db.php` (the app connects to it)

## Running the App
1. Open your browser.
2. Go to:
   - `http://localhost/Online%20Food/`
3. You should see the CraveRush homepage.

If your folder name is different, use:
- `http://localhost/<your-folder-name>/`

## Demo Credentials
Use these to log in immediately:
- Admin: `admin@craverush.test` / `admin123`
- Customer: `customer@craverush.test` / `customer123`

## Key Pages and Flows
Customer flow:
- `index.php` - Discover restaurants
- `restaurants.php` - Search and filter
- `restaurant.php?id=1` - View menu and add items
- `customer/cart.php` - Review cart
- `customer/place_order.php` - Place order
- `customer/orders.php` - Order history
- `customer/track.php` - Live tracking

Admin flow:
- `admin/index.php` - Dashboard
- `admin/restaurants.php` - Manage restaurants
- `admin/menu_items.php` - Manage menu items
- `admin/orders.php` - Manage orders
- `admin/reports.php` - Reports

## Seed Data Explained
The seed data includes:
- Two demo users (admin and customer)
- Addresses and payments for the demo customer
- Restaurants and menu items across multiple Indian cities

This makes the UI look pan-India by default.

## Troubleshooting Guide
- **Blank page**: check PHP error logs or enable error display.
- **Database error**: verify credentials in `config/db.php` and ensure MySQL is running.
- **Import failed**: confirm the database exists and that your MySQL user has permission.
- **Images not loading**: confirm the app is being served from the correct base URL.

## Security Notes
This is a demo app. Before production use:
- Remove or change seed credentials
- Harden authentication and authorization
- Validate and sanitize all inputs
- Use HTTPS and secure session settings

## Contributing
Contributions are welcome. Please keep changes focused and include clear commit messages.

## License
No license file is included yet. Add one if you plan to make this public or open source.
