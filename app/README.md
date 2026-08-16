# 🌾 AgroLink

> A role-based agricultural e-commerce and logistics platform connecting Farmers, Buyers, Transporters, and Admins.

AgroLink is a full-stack web application built as a **2nd Year Group Project** at **[University Name]**. It digitises the agricultural supply chain by enabling farmers to list produce, buyers to purchase and track orders, transporters to manage deliveries, and administrators to oversee the entire platform.

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Features by Role](#features-by-role)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Setup Instructions](#setup-instructions)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [Default Credentials](#default-credentials)
- [Team Members](#team-members)
- [License](#license)

---

## Project Overview

AgroLink solves the disconnect between Sri Lankan farmers and buyers by providing:

- A **marketplace** where farmers can list crops and buyers can browse and order
- A **crop request system** where buyers post what they need and farmers respond
- A **logistics layer** with distance-based shipping cost calculation and transporter assignment
- A **review system** for product feedback
- A **centralized admin panel** for platform management, analytics, and reporting

---

## Features by Role

### 🛒 Buyer
- Register, log in, and manage profile (address, photo, refund bank details)
- Browse products and add to cart or wishlist
- Checkout with smart shipping cost calculation (split by farmer location)
- Place crop requests with status tracking
- View and track orders in real time
- Submit product reviews and ratings
- Manage refund bank account details
- Deactivate account

### 🧑‍🌾 Farmer
- List and manage crop products (with images, pricing, quantity, location)
- View and respond to buyer crop requests
- Track incoming orders and update status
- View sales analytics and revenue reports
- Manage farm profile and verification documents

### 🚚 Transporter
- View available delivery requests
- Accept and manage active deliveries
- Update delivery status (pickup → in transit → delivered)
- Manage registered vehicles

### 🛡️ Admin
- Full user management (Buyers, Farmers, Transporters, Admins)
- Product and order oversight
- Delivery request and vehicle management
- Review and complaint moderation
- Platform analytics with Chart.js dashboards
- CSV export for reports (vehicles, reviews, orders, users)
- Platform settings management
- Superadmin-only admin account creation

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, CSS3 (Vanilla), JavaScript (ES6+) |
| **Backend** | PHP 8+ (Custom MVC Framework) |
| **Database** | MySQL 8 |
| **Server** | Apache (via XAMPP) |
| **Charts** | Chart.js (CDN) |
| **Architecture** | MVC (Model-View-Controller) |
| **Auth** | Session-based authentication with role guards |
| **Routing** | Custom PHP router (`app/core/app.php`) |

---

## Project Structure

```
agrolink/
├── app/
│   ├── controllers/
│   │   ├── admin/          # Admin dashboard controllers
│   │   ├── buyer/          # Buyer module controllers
│   │   │   ├── BuyerProfileController.php
│   │   │   ├── CartController.php
│   │   │   ├── CheckoutController.php
│   │   │   ├── CropRequestController.php
│   │   │   ├── BuyerOrdersController.php
│   │   │   ├── WishlistController.php
│   │   │   └── ...
│   │   ├── farmer/         # Farmer module controllers
│   │   ├── transporter/    # Transporter module controllers
│   │   ├── LoginController.php
│   │   ├── RegisterController.php
│   │   └── ...
│   ├── core/
│   │   ├── app.php         # Router / front controller
│   │   ├── config.php      # App config (DB credentials, ROOT URL)
│   │   ├── Database.php    # PDO database wrapper
│   │   ├── Model.php       # Base model trait (CRUD helpers)
│   │   ├── Controller.php  # Base controller trait
│   │   └── AuthHelper.php  # Auth/session helpers
│   ├── models/
│   │   ├── buyer/          # Buyer-specific models
│   │   ├── farmer/         # Farmer-specific models
│   │   ├── CropRequestModel.php
│   │   ├── SimpleShippingCalculator.php
│   │   └── ...
│   └── views/
│       ├── admin/          # Admin dashboard views
│       ├── buyer/          # Buyer views (dashboard, cart, orders, etc.)
│       ├── farmer/         # Farmer views
│       ├── transporter/    # Transporter views
│       ├── home.view.php
│       ├── login.view.php
│       └── register.view.php
├── database/
│   ├── agrolink.sql        # Full database dump (import this)
│   └── migrations/         # Individual migration files
├── public/
│   ├── index.php           # Application entry point
│   ├── .htaccess           # Apache URL rewriting rules
│   └── assets/
│       ├── css/            # Stylesheets per role/page
│       ├── js/             # JavaScript per role/page
│       └── images/         # Uploaded product and profile images
└── README.md
```

---

## Setup Instructions

### Prerequisites

Make sure you have the following installed:

- [XAMPP](https://www.apachefriends.org/) (PHP 8+, Apache, MySQL)
- A web browser
- Git (optional)

---

### Step 1 — Clone or Download the Project

**Option A — Git:**
```bash
git clone https://github.com/[your-username]/agrolink.git
```

**Option B — Manual:**
Download the ZIP and extract it.

---

### Step 2 — Place in XAMPP's htdocs

Move the project folder into your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\agrolink\
```

---

### Step 3 — Start XAMPP Services

Open the **XAMPP Control Panel** and start:
- ✅ **Apache**
- ✅ **MySQL**

---

### Step 4 — Database Setup

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Create a database named: `agrolink`
4. Select the `agrolink` database
5. Click the **Import** tab
6. Click **Choose File** and select:
   ```
   agrolink/database/agrolink.sql
   ```
7. Click **Go** — all tables and seed data will be imported

---

### Step 5 — Configure the Application

Open `app/core/config.php` and verify/update these values:

```php
define('DBHOST', 'localhost');   // MySQL host
define('DBNAME', 'agrolink');    // Database name
define('DBUSER', 'root');        // MySQL username
define('DBPASS', '');            // MySQL password (empty by default in XAMPP)

define('DEBUG', true);           // Set to false in production
```

> **Note:** The `ROOT` URL is computed automatically based on your server setup. No manual change needed for standard XAMPP.

---

### Step 6 — Run the Application

Open your browser and navigate to:

```
http://localhost/agrolink/public
```

You should see the AgroLink homepage. ✅

---

## Default Credentials

> These are the seeded test accounts from the SQL dump. Change passwords after first login.

| Role | Email | Password |
|---|---|---|
| **Admin / Superadmin** | `admin@agrolink.com` | `Admin@123` |
| **Buyer** | `buyer@agrolink.com` | `Buyer@123` |
| **Farmer** | `farmer@agrolink.com` | `Farmer@123` |
| **Transporter** | `transporter@agrolink.com` | `Trans@123` |

> ⚠️ Update these credentials in the database or via the platform settings before sharing access with others.

---

## Configuration Reference

| Constant | Location | Description |
|---|---|---|
| `ROOT` | `config.php` | Auto-computed base URL |
| `DBHOST` | `config.php` | Database host |
| `DBNAME` | `config.php` | Database name |
| `DBUSER` | `config.php` | Database username |
| `DBPASS` | `config.php` | Database password |
| `DEBUG` | `config.php` | Show PHP errors (`true` = dev, `false` = production) |

---

## Troubleshooting

**Blank page or 404 errors**
- Make sure Apache's `mod_rewrite` is enabled in XAMPP
- Verify the `public/.htaccess` file is present and not blocked

**Database connection error**
- Confirm MySQL is running in the XAMPP Control Panel
- Double-check credentials in `app/core/config.php`

**Images not showing**
- Ensure the `public/assets/images/` directory exists and is writable
- On Linux/Mac: `chmod -R 775 public/assets/images/`

**Session issues / can't log in**
- Clear browser cookies for `localhost`
- Restart Apache in XAMPP

---

## Team Members

| Name | Role | Module |
|---|---|---|
| [Yomal Chandima] | Buyer Module Developer | Buyer dashboard, cart, checkout, orders, crop requests, profile |
| [Sewni Jayawardena] | Farmer Module Developer | Product listings, crop request responses, farmer dashboard |
| [Kalmith Dissanayake] | Transporter Module Developer | Delivery management, vehicle tracking, transporter dashboard |
| [Ahamadh Saadhiq] | Admin & Architecture | Admin panel, user management, analytics, platform settings |

> 📍 **Institution:** University of Colombo School of Computing 
> 📅 **Year:** 2025 — 2nd Year Group Project

---

## License

This project was developed for academic purposes as part of a university group project.
All rights reserved © [2025]
---

<div align="center">
  <strong>Built  by Yomal, Sewni, Kalmith & Saadhiq</strong><br>
  <em>Connecting Farmers, Buyers & Transporters — One Harvest at a Time 🌾</em>
</div>
