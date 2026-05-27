# E-Clothing Store Project Documentation

## Overview

E-Clothing Store is a PHP and MySQL based ecommerce application for clothing products. The system includes a public storefront, customer account area, cart and checkout flow, product reviews, wishlist, and an admin panel for day-to-day store management.

## Technology Stack

- Backend: PHP with MySQLi
- Database: MySQL
- Frontend: HTML, CSS, Bootstrap, JavaScript
- Maps: Leaflet with OpenStreetMap tiles and Nominatim reverse geocoding
- Mail: SMTP settings managed from the admin panel
- Runtime target: Laragon/Apache/PHP on Windows

## Project Structure

- `index.php` - homepage and main storefront entry.
- `settings.php` - database host, port, database name, username, password, and base URL.
- `includes/` - shared database, store, SMTP, slider, page, variant, and order helper files.
- `user/` - customer-facing pages such as shop, search, login, signup, cart, checkout, profile, wishlist, and orders.
- `Admin/` - admin dashboard, orders, customers, reports, sliders, pages, SMTP, and settings.
- `product/` - admin product create/edit/list pages.
- `category/` - admin category management pages.
- `assets/images/` - uploaded product, slider, profile, and store images.
- `design-assets/` and `assets/css/` - storefront and admin styling.
- `e_clothing_store.sql` - database export for setup or deployment.

## Database Setup

Import `e_clothing_store.sql` into MySQL. The export includes the full schema and current sample/store data.

```bash
mysql -u root -p < e_clothing_store.sql
```

For Laragon with the included configuration:

```text
Host: 127.0.0.1
Port: 3304
Database: E_Clothing_Store
User: root
Password: blank
```

Update credentials in `settings.php` when moving to another machine or hosting provider.

## Admin Access

```text
Admin URL: http://localhost/ecloths/Admin/Adminlogin.php
Email: deepbist123456@gmil.com
Password: Dipa@123
```

Change this password before using the project in production.

## Storefront Workflow

Customers can browse the homepage, categories, shop page, and search page. Product cards link to detail pages where customers can select available variants, review stock, add to cart, buy now, add to wishlist, and submit reviews after login.

The header search sends users to the dedicated search page. Product listings are responsive and optimized for ecommerce browsing with filters, categories, and consistent card sizing.

## Product Variants

Variants are stored in the `productdetail` table. Each variant can include:

- Product ID
- Variation key, such as Size, Color, or Fit
- Variation value
- Variant SKU
- Price adjustment
- Variant stock quantity

The product detail page now sends the selected `variant_id` into the cart. Cart, checkout, order placement, customer orders, and admin orders preserve the selected option using `orderdetail.variant_id`, `orderdetail.variant_label`, and `orderdetail.variant_sku`.

## Checkout And Orders

Checkout uses Cash on Delivery only. Nepal is selected by default for shipping, with standard and express Nepal delivery options. The map picker opens in a popup, lets the customer click a delivery point, reverse geocodes the location, and stores coordinates plus address details with the order.

Orders include:

- Customer name, email, and phone
- Shipping address and map location details
- Product names and variants
- Quantity, unit price, shipping charge, and total
- Order status
- Tracking ID

Customers can view status and tracking from `My Orders`. Admin users can update order status to Pending, Processing, Shipped, Delivered, or Cancelled. Shipped and Delivered orders can include a courier tracking ID.

## Admin Panel

The admin panel manages:

- Dashboard metrics and latest orders
- Product create/edit/list with variant rows
- Categories
- Customers with total orders, total spent, phone, and address
- Orders with search, status update, tracking ID, customer phone, and delivery details
- Sliders with uploaded image preview and clickable links
- Footer pages such as About, Terms, Privacy, and related content
- SMTP settings
- Store settings including currency
- Reports

The admin panel includes responsive table cards and sticky order actions so controls remain usable on small screens and browser zoom.

## SMTP

SMTP settings are configured in the admin panel under `SMTP Settings`. Password reset and order email messages use the saved SMTP configuration. Password reset messages intentionally say that an email was sent if the address exists, which avoids exposing whether an email is registered.

## Mobile Support

The storefront uses responsive Bootstrap layouts and custom CSS for cards, header search, cart, checkout, account pages, and product detail pages. The admin panel uses responsive top navigation, sidebar grids, mobile table cards, scrollable modals, and sticky action controls for wide order tables.

## Deployment Notes

- Keep `settings.php` updated for the target database.
- Run `composer install` inside `user/` after cloning because `user/vendor/` is ignored.
- Ensure `assets/images/` is writable when product, slider, or profile image upload is needed.
- Configure SMTP before testing password reset or order emails.
- Change admin credentials before production use.

## Verification Checklist

- Homepage loads and slider controls work.
- Header search opens the dedicated search page.
- Product detail page allows variant selection and updates price/stock.
- Cart stores separate variants as separate line items.
- Checkout stores phone, email, address, and map location details.
- Admin orders display latest orders with phone, address, status, tracking, and actions.
- Customer orders show status and tracking ID.
- Admin panel remains usable on mobile and zoomed screens.
