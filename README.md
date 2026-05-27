# E-Clothing Store

PHP/MySQL ecommerce website for clothing products with storefront browsing, product variants, cart, checkout, customer accounts, reviews, wishlists, and a complete admin panel.

## Quick Setup

1. Copy the project folder to your web root, for example `C:\laragon\www\ecloths`.
2. Import the database file:

```bash
mysql -u root -p < e_clothing_store.sql
```

For the included Laragon setup, the database name is `E_Clothing_Store`.

3. Put database credentials in `settings.php`:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3304');
define('DB_NAME', 'E_Clothing_Store');
define('DB_USER', 'root');
define('DB_PASS', '');
```

4. Install PHP dependencies for the user mail/contact pages:

```bash
cd user
composer install
```

5. Open the website:

```text
http://localhost/ecloths/
```

## Admin Login

```text
Email: deepbist123456@gmil.com
Password: Dipa@123
```

## Important Files

- `settings.php` - database and base URL settings.
- `e_clothing_store.sql` - full MySQL database export.
- `docs/PROJECT_DOCUMENTATION.md` - full project documentation.
- `docs/E-Clothing-Store-Project-Presentation.pptx` - project PowerPoint presentation.

## Main Features

- Professional homepage slider and ecommerce product sections.
- Header search with dedicated search results page.
- Product detail page with selectable variants, adjusted price, variant stock, add to cart, buy now, reviews, and wishlist.
- Cart and checkout with Cash on Delivery, Nepal shipping, and OpenStreetMap location picker.
- Customer dashboard, orders with status/tracking, wishlist, and profile.
- Admin dashboard, products, variants, categories, sliders, pages, SMTP settings, reports, customers, and orders.
- Mobile friendly storefront and admin panel layouts.
