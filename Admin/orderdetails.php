<?php
include '../includes/header.php';
require_once __DIR__ . '/../includes/order_helpers.php';
require_once __DIR__ . '/../includes/product_variants.php';
ensure_order_contact_schema($con);
ensure_orderdetail_variant_schema($con);

$search_condition = "";
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $search_condition = "AND (o.id LIKE '%$search%' OR u.name LIKE '%$search%' OR o.name LIKE '%$search%' OR s.mobile LIKE '%$search%' OR s.shipping_address LIKE '%$search%')";
}

$sql = "
    SELECT 
        o.id AS order_id,
        o.name AS order_name,
        o.order_status,
        o.tracking_id,
        o.shipping_charge,
        o.created_at,
        s.billing_address,
        s.shipping_address,
        s.mobile AS customer_phone,
        s.shipping_location_details,
        u.name AS user_name,
        COALESCE(s.email, u.email) AS customer_email,
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
                DISTINCT CONCAT(
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
    WHERE o.deleted_at IS NULL $search_condition
    ORDER BY o.created_at DESC, o.id DESC
";

$res = mysqli_query($con, $sql);
?>

<!-- <style>
    th, .table td, .table th {
        text-align: center;
    }

    .blue-hover tbody tr:hover {
        background-color: rgb(215, 218, 218);
        box-shadow: 0 0 10px rgba(228, 238, 246, 0.2);
        transition: all 0.3s ease-in-out;
    }

    .page-header h1 {
        text-align: center;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 20px;
        border-radius: 10px;
        max-width: 600px;
        position: relative;
        text-align: center;
    }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 24px;
        cursor: pointer;
    }

    .modal-product-images img {
        width: 100px;
        margin: 5px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }
</style> -->
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

    .modal-content{
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

    .modal-product-images img {
        width: 100px;
        height: auto;
        margin: 5px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
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
        <h1><i class="fas fa-box-open"></i> Order Details</h1>
    </header>

    <div class="search-wrapper">
        <input type="search" id="searchInput" placeholder="Search by ID, product Name or Username..." autocomplete="off" aria-label="Search products" />
        <button type="button" class="page-close-btn" title="Back to Dashboard" onclick="window.location.href='Admindashboard.php'">
            &times;
        </button>
    </div>

     <div class="table-container" role="region" aria-live="polite" aria-relevant="all">
        <table id="usertTable" class="user-table" aria-label="List of user">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Product Name(s)</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Order Status</th>
                    <th>Tracking ID</th>
                    <th>Billing Address</th>
                    <th>Shipping Address</th>
                    <th>Shipping Charge (Rs)</th>
                    <th>Total Quantity</th>
                    <th>Total (Rs)</th>
                    <th>Order Date</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res && mysqli_num_rows($res) > 0): ?>
                    <?php while ($order = mysqli_fetch_assoc($res)): ?>
                        <tr>
                            <td><?= $order['order_id'] ?></td>
                            <td><?= htmlspecialchars($order['order_name'] ?: $order['user_name']) ?></td>
                            <td><?= htmlspecialchars($order['product_names']) ?></td>
                            <td><?= htmlspecialchars($order['customer_email']) ?></td>
                            <td><?= htmlspecialchars($order['customer_phone'] ?: 'Not provided') ?></td>
                            <td><?= htmlspecialchars($order['order_status']) ?></td>
                            <td><?= htmlspecialchars($order['tracking_id'] ?: 'Not assigned yet') ?></td>
                            <td><?= htmlspecialchars($order['billing_address']) ?></td>
                            <td><?= htmlspecialchars($order['shipping_address']) ?></td>
                            <td><?= number_format($order['shipping_charge'], 2) ?></td>
                            <td><?= (int) $order['total_quantity'] ?></td>
                            <td><?= number_format($order['total_price'], 2) ?></td>
                            <td><?= date('Y-m-d h:i A', strtotime($order['created_at'])) ?></td>
                            <td class="text-center">
                                <div class="actions">
                                    <button class="btn view-btn" onclick='openOrderModal(<?= json_encode($order) ?>)'>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="Orderdetailsdelete.php?id=<?= $order['order_id'] ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this Order Details?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="14" class="text-center text-muted">No order details found.</td></tr>
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
        <div class="modal-content">
            <span class="close-btn" onclick="closeOrderModal()">&times;</span>
            <h2 class="modal-title"><i class="fas fa-box"></i> Order Details</h2>
            <p><strong>Customer:</strong> <span id="modalUserName"></span></p>
            <p><strong>Email:</strong> <span id="modalEmail"></span></p>
            <p><strong>Phone:</strong> <span id="modalPhone"></span></p>
            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
            <p><strong>Tracking ID:</strong> <span id="modalTracking"></span></p>
            <p><strong>Billing Address:</strong> <span id="modalBilling"></span></p>
            <p><strong>Shipping Address:</strong> <span id="modalShipping"></span></p>
            <p id="modalLocationWrap"><strong>Location Details:</strong> <span id="modalLocation"></span></p>
            <div id="modalProducts"></div>
            <div class="modal-product-images" id="modalImages"></div>
            <p><strong>Shipping Charge:</strong> Rs <span id="modalShippingCharge"></span></p>
            <p><strong>Subtotal:</strong> Rs <span id="modalSubtotal"></span></p>
            <p><strong>Total:</strong> Rs <span id="modalTotal"></span></p>
        </div>
    </div>
</div>

<script>
    function openOrderModal(order) {
        document.getElementById('modalUserName').innerText = order.user_name;
        document.getElementById('modalEmail').innerText = order.customer_email || 'Not provided';
        document.getElementById('modalPhone').innerText = order.customer_phone || 'Not provided';
        document.getElementById('modalStatus').innerText = order.order_status || 'Pending';
        document.getElementById('modalTracking').innerText = order.tracking_id || 'Not assigned yet';
        document.getElementById('modalBilling').innerText = order.billing_address;
        document.getElementById('modalShipping').innerText = order.shipping_address;
        const locationText = order.shipping_location_details || '';
        document.getElementById('modalLocation').innerText = locationText || 'Not provided';
        document.getElementById('modalLocationWrap').style.display = locationText ? '' : 'none';
        document.getElementById('modalShippingCharge').innerText = parseFloat(order.shipping_charge).toFixed(2);
        document.getElementById('modalSubtotal').innerText = (order.total_price - order.shipping_charge).toFixed(2);
        document.getElementById('modalTotal').innerText = parseFloat(order.total_price).toFixed(2);

        // Product names and quantity
        let productHTML = `
            <p><strong>Products:</strong> ${order.product_names}</p>
            <p><strong>Total Quantity:</strong> ${order.total_quantity}</p>
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

    document.getElementById('searchInput').addEventListener('input', function () {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('#usertTable tbody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
    
// Pagination Logic
const rows = Array.from(document.querySelectorAll('#usertTable tbody tr'));
const rowsPerPage = 10;
let currentPage = 1;
const totalPages = Math.ceil(rows.length / rowsPerPage);

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
