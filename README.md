# E-Clothing Store

Minor Project By Nabin Koirala, Dinesh Phulara & Padam Dhami

E-Clothing Store is a PHP/MySQL ecommerce website for clothing products. It includes a customer storefront, product variants, cart, checkout, customer accounts, reviews, wishlists, order tracking, and a full admin panel.

## Recommended Folder Name

Use this project folder name:

```text
ecloth
```

Recommended Laragon/XAMPP path:

```text
C:\laragon\www\ecloth
```

Recommended local URL:

```text
http://localhost/ecloth/
```

## Requirements

- PHP 8.0 or newer
- MySQL or MariaDB
- Apache server through Laragon, XAMPP, WAMP, or similar
- Composer, recommended for refreshing PHP dependencies
- PHP extensions commonly enabled in local stacks: `mysqli`, `mbstring`, `openssl`, `curl`, `json`, `fileinfo`

## Download From GitHub

Clone the project into the `ecloth` folder:

```bash
cd C:\laragon\www
git clone https://github.com/herozero2/e-clothingstore.git ecloth
```

You can also download the ZIP from GitHub and rename the extracted folder to `ecloth`.

## Database Setup

1. Create/import the database using the included SQL file:

```bash
mysql -u root -p < e_clothing_store.sql
```

The SQL file creates and uses this database:

```text
E_Clothing_Store
```

2. If you use phpMyAdmin, open phpMyAdmin and import:

```text
e_clothing_store.sql
```

3. Update database credentials in [settings.php](settings.php):

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3304');
define('DB_NAME', 'E_Clothing_Store');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Use `DB_PORT` `3304` for the included Laragon setup. If your MySQL uses the default port, change it to `3306`.

## Composer Install

Required PHPMailer runtime files are already included so contact/order emails do not fail after a GitHub download.

Still, running Composer is recommended after cloning if you want to refresh all PHP dependencies:

```bash
cd C:\laragon\www\ecloth\user
composer install
```

If Composer is not installed, the main ecommerce site and contact mail runtime can still work with the committed files.

## Open The Website

Storefront:

```text
http://localhost/ecloth/
```

Admin panel:

```text
http://localhost/ecloth/Admin/Adminlogin.php
```

## Admin Login

```text
Dinesh Phulara
Email: dinesh@gmail.com
Password: @Fwu1234

Nabin Koirala
Email: nabin@gmail.com
Password: @Fwu1234
```

## Customer Features

- Homepage with professional slider and ecommerce product sections
- Header search with dedicated search page
- Shop page with product filtering and categories
- Product detail page with variants, adjusted price, stock, reviews, wishlist, cart, and buy now
- Cart and checkout with Cash on Delivery
- Nepal shipping and OpenStreetMap location picker
- Customer dashboard, profile, wishlist, and orders
- Order status and courier tracking ID visible to customers
- Forgot password and SMTP email support

## Admin Features

- Dashboard with products, customers, orders, and revenue overview
- Product create/edit with image upload and variants
- Category management
- Slider management with clickable uploaded images
- Pages manager for footer pages like About Us, Privacy Policy, Terms, Return Policy, and FAQs
- SMTP settings page
- Customers page with order count, total spent, phone, and address
- Orders page with status/tracking update modal
- Reports section with sales and customer data
- Admin-wide page search
- Mobile-friendly admin layout

## Important Files

- [settings.php](settings.php) - database and base URL settings
- [e_clothing_store.sql](e_clothing_store.sql) - complete MySQL database export
- [includes/phpmailer_loader.php](includes/phpmailer_loader.php) - PHPMailer runtime loader for GitHub downloads
- [docs/PROJECT_DOCUMENTATION.md](docs/PROJECT_DOCUMENTATION.md) - full project documentation
- [docs/E-Clothing-Store-Project-Presentation.pptx](docs/E-Clothing-Store-Project-Presentation.pptx) - project PowerPoint presentation

## SMTP Settings

SMTP can be updated from:

```text
Admin Panel > SMTP Settings
```

Default store email is:

```text
help@example.com
```

Use real SMTP credentials when sending real emails.

## Troubleshooting

If the database does not connect:

- Check `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `settings.php`.
- Make sure MySQL is running.
- Re-import `e_clothing_store.sql`.

If pages show missing image icons:

- Make sure the `assets/images` folder was copied with the project.
- Do not remove product and slider images referenced by the database.

If email does not send:

- Open `Admin Panel > SMTP Settings`.
- Add valid SMTP host, port, username, password, from email, and admin email.
- Check your local PHP error log for SMTP errors.

If Composer packages are missing:

```bash
cd C:\laragon\www\ecloth\user
composer install
```

## Project Status

This project is ready for local demonstration with the included SQL file, admin credentials, product data, images, documentation, and PowerPoint presentation.
