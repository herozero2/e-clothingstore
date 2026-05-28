<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/order_helpers.php';
require_once __DIR__ . '/../includes/product_variants.php';
$con = require_admin(null, 'Adminlogin.php');
ensure_order_contact_schema($con);
ensure_orderdetail_variant_schema($con);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['order_status'])) {
    $order_id = (int) $_POST['order_id'];
    $new_status = mysqli_real_escape_string($con, $_POST['order_status']);
    $tracking_id = trim(mysqli_real_escape_string($con, $_POST['tracking_id'] ?? ''));
    $confirmed_cancel = isset($_POST['confirmed_cancel']) ? (int) $_POST['confirmed_cancel'] : 0;

    if ($new_status === 'Cancelled' && $confirmed_cancel === 1) {
        $get_products = "SELECT product_id, variant_id, quantity FROM orderdetail WHERE order_id = $order_id";
        $res_products = mysqli_query($con, $get_products);
        while ($row = mysqli_fetch_assoc($res_products)) {
            $product_id = (int) $row['product_id'];
            $variant_id = (int) ($row['variant_id'] ?? 0);
            $qty = (int) $row['quantity'];
            mysqli_query($con, "UPDATE product SET quantity = quantity + $qty WHERE id = $product_id");
            if ($variant_id > 0) {
                mysqli_query($con, "UPDATE productdetail SET quantity = quantity + $qty WHERE id = $variant_id AND product_id = $product_id");
            }
        }
        mysqli_query($con, "UPDATE orders SET order_status='Cancelled', deleted_at = NOW() WHERE id = $order_id");
    } else {
        if (in_array($new_status, ['Shipped', 'Delivered'], true) && $tracking_id === '') {
            $tracking_id = 'NP-Courier-' . str_pad((string) $order_id, 5, '0', STR_PAD_LEFT);
        }
        $trackingSql = $tracking_id !== '' ? "'" . $tracking_id . "'" : "NULL";
        mysqli_query($con, "UPDATE orders SET order_status='$new_status', tracking_id=$trackingSql WHERE id=$order_id");
    }

    header("Location: Adminorders.php");
    exit();
}

include '../includes/header.php';

$sql = "
    SELECT 
        o.id AS order_id, o.name AS order_name, o.order_status, o.payment_method, o.tracking_id, o.created_at,o.shipping_charge,
        u.name AS user_name, COALESCE(s.email, u.email) AS customer_email, COALESCE(s.mobile, '') AS customer_phone,
        COALESCE(s.shipping_address, '') AS shipping_address, COALESCE(s.shipping_location_details, '') AS shipping_location_details,
        COALESCE(order_items.product_names, '') AS product_names,
        COALESCE(order_items.product_images, '') AS product_images,
        COALESCE(order_items.total_quantity, 0) AS total_quantity,
        COALESCE(order_items.items_total, 0) + o.shipping_charge AS total_price
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN (
        SELECT s1.*
        FROM shipping s1
        INNER JOIN (
            SELECT order_id, MAX(id) AS latest_shipping_id
            FROM shipping
            WHERE deleted_at IS NULL
            GROUP BY order_id
        ) latest_shipping ON latest_shipping.latest_shipping_id = s1.id
    ) s ON s.order_id = o.id
    LEFT JOIN (
        SELECT
            od.order_id,
            GROUP_CONCAT(
                CONCAT(
                    p.name,
                    IF(od.variant_label IS NOT NULL AND od.variant_label <> '', CONCAT(' (', od.variant_label, ')'), '')
                )
                SEPARATOR ', '
            ) AS product_names,
            GROUP_CONCAT(DISTINCT p.image SEPARATOR ', ') AS product_images,
            SUM(od.quantity) AS total_quantity,
            SUM(od.unit_price * od.quantity) AS items_total
        FROM orderdetail od
        LEFT JOIN product p ON od.product_id = p.id
        WHERE od.deleted_at IS NULL
        GROUP BY od.order_id
    ) order_items ON order_items.order_id = o.id
    WHERE o.deleted_at IS NULL
    ORDER BY o.created_at DESC, o.id DESC
";

$res = mysqli_query($con, $sql);
?>

<style>
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow-y: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 25px;
        border-radius: 10px;
        max-width: 600px;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        animation: fadeIn 0.3s ease-in-out;
        text-align: center;
    }

    .modal-content.order-view-content {
        max-width: 760px;
        width: min(94vw, 760px);
        max-height: 88vh;
        overflow-y: auto;
        text-align: left;
    }

    .modal-content.order-update-content {
        text-align: left;
    }

    .order-view-content .modal-title {
        margin-bottom: 16px;
    }

    .order-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .order-detail-grid p,
    .order-detail-products p {
        margin: 0;
        padding: 10px 12px;
        border: 1px solid #dde5ef;
        border-radius: 8px;
        background: #f8fafc;
        overflow-wrap: anywhere;
    }

    .order-detail-wide {
        grid-column: 1 / -1;
    }

    .order-detail-products {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .order-summary-line {
        margin: 10px 0 0;
        padding: 10px 12px;
        border: 1px solid #dde5ef;
        border-radius: 8px;
        background: #fff;
        overflow-wrap: anywhere;
    }

    .modal-product-images img {
        width: 100px;
        height: auto;
        margin: 5px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Responsive for small screens */
    @media (max-width: 768px) {

        .table th,
        .table td {
            font-size: 12px;
            padding: 8px 10px;
        }

        .modal-content {
            width: 90%;
        }

        .order-detail-grid,
        .order-detail-products {
            grid-template-columns: 1fr;
        }
    }

    /* Button Styling */
    .pagination-controls .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background-color: #007BFF;
        color: #fff;
        border: none;
        font-size: 1.1rem;
        padding: 0.7rem 1.5rem;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.3s, transform 0.2s;
        font-weight: bold;
    }

    .pagination-controls .btn:hover {
        background-color: #0056b3;
        transform: scale(1.05);
    }

    .pagination-controls .btn:disabled {
        background-color: #ccc;
        cursor: not-allowed;
        transform: none;
    }

    .pagination-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.5rem;
        padding: 0 2rem;
    }
</style>
<div class="dashboard-content">
    <header class="page-header center-content text-center">
        <h1><i class="fas fa-box"></i> Orders Management</h1>
    </header>

    <div class="search-wrapper">
        <input type="search" id="searchInput" placeholder="Search by ID, Product Name, customer names , ..."
            autocomplete="off" aria-label="Search orders" />
        <button type="button" class="page-close-btn" title="Back to Dashboard"
            onclick="window.location.href='Admindashboard.php'">&times;</button>
    </div>

    <div class="table-container responsive-table-container">
        <table id="orderTable" class="user-table admin-responsive-table admin-orders-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th class="laptop-popup-only">Email</th>
                    <th class="laptop-popup-only">Phone</th>
                    <th class="laptop-popup-only">Delivery Address</th>
                    <th>Order Date</th>
                    <th class="laptop-popup-only">Payment Method</th>
                    <th class="laptop-popup-only">Product Names</th>
                    <th class="laptop-popup-only">Shipping Charge</th>
                    <th class="laptop-popup-only">Total Qty</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>Tracking ID</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res && mysqli_num_rows($res) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($res)): ?>
                        <?php $orderJson = htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8'); ?>
                        <tr>
                            <td data-label="Order ID" class="mobile-keep">#<?= $order['order_id'] ?></td>
                            <td data-label="Customer" class="mobile-keep"><?= htmlspecialchars($order['order_name']) ?: htmlspecialchars($order['user_name']) ?></td>
                            <td data-label="Email" class="mobile-popup-only laptop-popup-only"><?= htmlspecialchars($order['customer_email'] ?? '') ?></td>
                            <td data-label="Phone" class="mobile-popup-only laptop-popup-only"><?= htmlspecialchars($order['customer_phone'] ?: 'Not provided') ?></td>
                            <td data-label="Delivery Address" class="mobile-popup-only laptop-popup-only" title="<?= htmlspecialchars($order['shipping_address'] ?: 'Not provided') ?>">
                                <?= htmlspecialchars(compact_order_address($order['shipping_address'] ?? '', 72)) ?>
                            </td>
                            <td data-label="Order Date" class="mobile-keep"><?= date('Y-m-d h:i A', strtotime($order['created_at'])) ?></td>
                            <td data-label="Payment Method" class="mobile-popup-only laptop-popup-only"><?= htmlspecialchars($order['payment_method']) ?></td>
                            <td data-label="Products" class="mobile-popup-only laptop-popup-only"><?= htmlspecialchars($order['product_names']) ?></td>
                            <td data-label="Shipping Charge" class="mobile-popup-only laptop-popup-only"><?= money((float) $order['shipping_charge'], $con) ?></td>
                            <td data-label="Total Qty" class="mobile-popup-only laptop-popup-only"><?= (int) $order['total_quantity'] ?></td>
                            <td data-label="Total Price" class="mobile-keep"><?= money((float) $order['total_price'], $con) ?></td>
                            <td data-label="Status" class="mobile-keep mobile-status-cell">
                                <?php $statusClass = 'status-' . strtolower((string) ($order['order_status'] ?: 'pending')); ?>
                                <span class="status-pill <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($order['order_status'] ?: 'Pending') ?></span>
                            </td>
                            <td data-label="Tracking ID" class="mobile-keep mobile-tracking-cell">
                                <span class="tracking-chip"><?= htmlspecialchars(trim((string) ($order['tracking_id'] ?? '')) ?: 'Not assigned') ?></span>
                            </td>
                            <td data-label="Actions" class="text-center mobile-actions">
                                <div class="actions">
                                    <button class="btn view-btn" onclick='openOrderModal(<?= $orderJson ?>)' title="View full order">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn edit-btn" onclick='openOrderUpdateModal(<?= $orderJson ?>)' title="Update status and tracking">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="Orderdetailsdelete.php?id=<?= $order['order_id'] ?>" class="btn delete-btn"
                                        onclick="return confirm('Are you sure you want to delete this Orders ?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="14" class="text-center text-muted">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Stylish Pagination Controls -->
        <div class="pagination-controls">
            <button id="prevBtn" class="btn" disabled><i class="fas fa-arrow-left"></i> Previous</button>
            <button id="nextBtn" class="btn">Next <i class="fas fa-arrow-right"></i></button>
        </div>
    </div>
    <!-- Order View Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content order-view-content">
            <span class="close-btn" onclick="closeOrderModal()">&times;</span>
            <h2 class="modal-title"><i class="fas fa-box"></i> Customer Order</h2>
            <div class="order-detail-grid">
                <p><strong>Customer:</strong> <span id="modalUserName"></span></p>
                <p><strong>Email Address:</strong> <span id="modalEmail"></span></p>
                <p><strong>Phone Number:</strong> <span id="modalPhone"></span></p>
                <p><strong>Order Date:</strong> <span id="modalDate"></span></p>
                <p class="order-detail-wide"><strong>Delivery Address:</strong> <span id="modalAddress"></span></p>
                <p class="order-detail-wide" id="modalLocationWrap"><strong>Location Details:</strong> <span id="modalLocation"></span></p>
                <p><strong>Payment Method:</strong> <span id="modalPayment"></span></p>
                <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                <p class="order-detail-wide"><strong>Tracking ID:</strong> <span id="modalTracking"></span></p>
            </div>
            <div id="modalProducts" class="order-detail-products"></div>
            <div class="modal-product-images" id="modalImages"></div>
            <p class="order-summary-line"><strong>Shipping Charge:</strong> <span id="modalShipping"></span></p>
            <p class="order-summary-line"><strong>Total:</strong> <span id="modalTotal"></span></p>
        </div>
    </div>
    <div id="orderUpdateModal" class="modal">
        <div class="modal-content order-update-content">
            <span class="close-btn" onclick="closeOrderUpdateModal()">&times;</span>
            <h2 class="modal-title"><i class="fas fa-edit"></i> Update Order</h2>
            <p class="text-muted" id="updateOrderMeta"></p>
            <form id="orderUpdateForm" method="POST" action="Adminorders.php" class="order-update-form">
                <input type="hidden" name="order_id" id="updateOrderId">
                <input type="hidden" name="confirmed_cancel" id="updateConfirmedCancel" value="0">
                <label>
                    <span>Order Status</span>
                    <select name="order_status" id="updateOrderStatus" class="form-select status-select" required>
                        <?php foreach (['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'] as $status): ?>
                            <option value="<?= $status ?>"><?= $status ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Tracking ID</span>
                    <input type="text" name="tracking_id" id="updateTrackingId" class="form-control tracking-input" placeholder="Courier tracking ID">
                </label>
                <button type="submit" class="btn edit-btn order-update-save">
                    <i class="fas fa-save"></i> Save Order Update
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function openOrderModal(order) {
        document.getElementById('modalUserName').innerText = order.order_name || order.user_name || 'Customer';
        document.getElementById('modalEmail').innerText = order.customer_email || 'Not provided';
        document.getElementById('modalPhone').innerText = order.customer_phone || 'Not provided';
        document.getElementById('modalAddress').innerText = order.shipping_address || 'Not provided';
        const locationWrap = document.getElementById('modalLocationWrap');
        const locationText = order.shipping_location_details || '';
        document.getElementById('modalLocation').innerText = locationText || 'Not provided';
        locationWrap.style.display = locationText ? '' : 'none';
        document.getElementById('modalDate').innerText = order.created_at;
        document.getElementById('modalPayment').innerText = order.payment_method || 'Cash on Delivery';
        document.getElementById('modalStatus').innerText = order.order_status || 'Pending';
        document.getElementById('modalTracking').innerText = order.tracking_id || 'Not assigned yet';
        document.getElementById('modalShipping').innerText = <?= json_encode(currency_symbol($con)) ?> + ' ' + parseFloat(order.shipping_charge || 0).toFixed(2);
        document.getElementById('modalTotal').innerText = <?= json_encode(currency_symbol($con)) ?> + ' ' + parseFloat(order.total_price).toFixed(2);

        // Product names and quantity
        let productHTML = `
            <p><strong>Products:</strong> ${escapeHtml(order.product_names || 'Not provided')}</p>
            <p><strong>Total Quantity:</strong> ${escapeHtml(order.total_quantity || 0)}</p>
        `;
        document.getElementById('modalProducts').innerHTML = productHTML;

        // Product images
        const imageContainer = document.getElementById('modalImages');
        imageContainer.innerHTML = '';
        if (order.product_images) {
            const images = order.product_images.split(', ');
            images.forEach(img => {
                const imagePath = `../assets/images/${img}`;
                imageContainer.innerHTML += `<img src=\"${imagePath}\" alt=\"Product Image\" />`;
            });
        }

        document.getElementById('orderModal').style.display = 'block';
    }

    function closeOrderModal() {
        document.getElementById('orderModal').style.display = 'none';
    }

    function openOrderUpdateModal(order) {
        document.getElementById('updateOrderId').value = order.order_id || '';
        document.getElementById('updateOrderStatus').value = order.order_status || 'Pending';
        document.getElementById('updateTrackingId').value = order.tracking_id || '';
        document.getElementById('updateConfirmedCancel').value = '0';
        document.getElementById('updateOrderMeta').innerText = `Order #${order.order_id} - ${order.order_name || order.user_name || 'Customer'}`;
        document.getElementById('orderUpdateModal').style.display = 'block';
    }

    function closeOrderUpdateModal() {
        document.getElementById('orderUpdateModal').style.display = 'none';
    }

</script>

<!-- Bootstrap JS First -->
<script src="../design-assets/js/bootstrap.bundle.min.js"></script>

<!-- Custom Scripts -->
<script>
    // Search Functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('#orderTable tbody tr');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });

    document.getElementById('orderUpdateForm')?.addEventListener('submit', function (event) {
        const selectedValue = this.querySelector('select[name="order_status"]').value;

        if (selectedValue === 'Cancelled') {
            if (!confirm("Do you want to cancel and hide this order?")) {
                event.preventDefault();
                return;
            }
            this.querySelector('input[name="confirmed_cancel"]').value = '1';
        } else {
            this.querySelector('input[name="confirmed_cancel"]').value = '0';
        }
    });

    // Pagination Logic
    const rows = Array.from(document.querySelectorAll('#orderTable tbody tr'));
    const rowsPerPage = 10;
    let currentPage = 1;
    const totalPages = Math.max(1, Math.ceil(rows.length / rowsPerPage));

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    function displayPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });
        prevBtn.disabled = page === 1;
        nextBtn.disabled = page === totalPages;
    }

    prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            displayPage(currentPage);
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            displayPage(currentPage);
        }
    });

    // Initialize first page
    displayPage(currentPage);
</script>

<?php include '../includes/footer.php'; ?>
