<?php
include '../includes/header.php';
require_once __DIR__ . '/../includes/order_helpers.php';
ensure_order_contact_schema($con);

$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        u.image,
        u.created_at,
        COUNT(DISTINCT o.id) AS total_orders,
        COALESCE(SUM(COALESCE(order_totals.items_total, 0) + COALESCE(o.shipping_charge, 0)), 0) AS total_spent,
        MAX(o.created_at) AS last_order_date,
        COALESCE((
            SELECT s.mobile
            FROM orders lo
            LEFT JOIN shipping s ON s.order_id = lo.id AND s.deleted_at IS NULL
            WHERE lo.user_id = u.id AND lo.deleted_at IS NULL AND s.mobile IS NOT NULL AND s.mobile <> ''
            ORDER BY lo.created_at DESC, lo.id DESC
            LIMIT 1
        ), '') AS phone,
        COALESCE((
            SELECT s.shipping_address
            FROM orders lo
            LEFT JOIN shipping s ON s.order_id = lo.id AND s.deleted_at IS NULL
            WHERE lo.user_id = u.id AND lo.deleted_at IS NULL AND s.shipping_address IS NOT NULL AND s.shipping_address <> ''
            ORDER BY lo.created_at DESC, lo.id DESC
            LIMIT 1
        ), '') AS latest_address
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id AND o.deleted_at IS NULL
    LEFT JOIN (
        SELECT order_id, SUM(quantity * unit_price) AS items_total
        FROM orderdetail
        WHERE deleted_at IS NULL
        GROUP BY order_id
    ) order_totals ON order_totals.order_id = o.id
    WHERE u.user_type != 'admin' AND u.deleted_at IS NULL
    GROUP BY u.id, u.name, u.email, u.image, u.created_at
    ORDER BY u.created_at DESC, u.id DESC
";
$res = mysqli_query($con, $sql);
?>

<style>
.profile-img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%;
    border: 2px solid #ccc;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}

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

.modal-users-images img {
    width: 100px;
    height: auto;
    margin: 5px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

.customer-address {
    max-width: 220px;
    line-height: 1.4;
}

.customer-metric {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 78px;
    min-height: 32px;
    padding: 0 10px;
    border-radius: 8px;
    background: #ecfdf5;
    color: #047857;
    font-weight: 800;
}

@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}

@media (max-width: 768px) {
    .table th, .table td {
        font-size: 12px;
        padding: 8px 10px;
    }
    .modal-content {
        width: 90%;
    }
}
</style>

<div class="dashboard-content">
    <header class="page-header center-content text-center">
        <h1><i class="fas fa-users"></i> Customer Details</h1>
    </header>

    <div class="search-wrapper">
        <input type="search" id="searchInput" placeholder="Search by ID, name, email, phone, order total..." autocomplete="off" aria-label="Search customers" />
        <button type="button" class="page-close-btn" title="Back to Dashboard" onclick="window.location.href='Admindashboard.php'">&times;</button>
    </div>

    <div class="table-container responsive-table-container" role="region" aria-live="polite" aria-relevant="all">
        <table id="usertTable" class="user-table admin-responsive-table admin-customers-table" aria-label="List of users">
            <thead>
                <tr>
                    <th>S.N</th>
                    <th>User ID</th>
                    <th>Profile Image</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Total Orders</th>
                    <th>Total Spent</th>
                    <th>Last Order</th>
                    <th>Latest Address</th>
                    <th>Joined Date</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res && mysqli_num_rows($res) > 0): $sn = 1; ?>
                    <?php while ($user = mysqli_fetch_assoc($res)):
                        $user['total_spent_display'] = money((float) $user['total_spent'], $con);
                        $user['last_order_display'] = $user['last_order_date'] ? date('Y-m-d h:i A', strtotime($user['last_order_date'])) : 'No orders yet';
                        $user['phone_display'] = trim((string) $user['phone']) ?: 'Not provided';
                        $user['latest_address_display'] = trim((string) $user['latest_address']) ?: 'Not provided';
                        $jsonData = htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8');
                    ?>
                    <tr>
                        <td data-label="S.N" class="mobile-popup-only"><?= $sn++ ?></td>
                        <td data-label="User ID" class="mobile-keep">#<?= htmlspecialchars($user['id']) ?></td>
                        <td data-label="Profile Image" class="mobile-popup-only">
                            <?php
                            $imagePath = '../assets/images/' . $user['image'];
                            $imgSrc = (!empty($user['image']) && str_starts_with($user['image'], 'http')) ? $user['image'] : ((!empty($user['image']) && file_exists($imagePath)) ? $imagePath : '../assets/images/avatar.jpg');
                            ?>
                            <img src="<?= $imgSrc ?>" class="profile-img" alt="Profile">
                        </td>
                        <td data-label="Name" class="mobile-keep"><?= htmlspecialchars($user['name']) ?></td>
                        <td data-label="Email" class="mobile-keep"><?= htmlspecialchars($user['email']) ?></td>
                        <td data-label="Phone" class="mobile-keep"><?= htmlspecialchars($user['phone_display']) ?></td>
                        <td data-label="Total Orders" class="mobile-keep"><span class="customer-metric"><?= (int) $user['total_orders'] ?></span></td>
                        <td data-label="Total Spent" class="mobile-keep"><?= htmlspecialchars($user['total_spent_display']) ?></td>
                        <td data-label="Last Order" class="mobile-popup-only"><?= htmlspecialchars($user['last_order_display']) ?></td>
                        <td data-label="Latest Address" class="customer-address mobile-popup-only" title="<?= htmlspecialchars($user['latest_address_display']) ?>">
                            <?= htmlspecialchars(compact_order_address($user['latest_address_display'], 70)) ?>
                        </td>
                        <td data-label="Joined Date" class="mobile-popup-only"><?= date('Y-m-d h:i A', strtotime($user['created_at'])) ?></td>
                        <td data-label="Action" class="text-center mobile-actions">
                            <div class="actions">
                                <button class="btn view-btn" data-user='<?= $jsonData ?>' onclick="viewUserDetails(this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="customerdelete.php?id=<?= $user['id'] ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this customer?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="12" class="text-center text-muted">No customers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Customer View Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeOrderModal()">&times;</span>
        <h2 class="modal-title"><i class="fas fa-user"></i> Customer Details </h2>
        <p><strong>Customer Name:</strong> <span id="modalUserName"></span></p>
        <p><strong>Email Address:</strong> <span id="modalEmail"></span></p>
        <p><strong>Phone Number:</strong> <span id="modalPhone"></span></p>
        <p><strong>Total Orders:</strong> <span id="modalTotalOrders"></span></p>
        <p><strong>Total Spent:</strong> <span id="modalTotalSpent"></span></p>
        <p><strong>Last Order:</strong> <span id="modalLastOrder"></span></p>
        <p><strong>Latest Address:</strong> <span id="modalAddress"></span></p>
        <p><strong>Joined Date:</strong> <span id="modalDate"></span></p>
        <div class="modal-users-images" id="modalImages"></div>
    </div>
</div>

<script>
// View user details
function viewUserDetails(button) {
    const user = JSON.parse(button.getAttribute('data-user'));
    document.getElementById('modalUserName').innerText = user.name;
    document.getElementById('modalEmail').innerText = user.email;
    document.getElementById('modalPhone').innerText = user.phone_display || 'Not provided';
    document.getElementById('modalTotalOrders').innerText = user.total_orders || '0';
    document.getElementById('modalTotalSpent').innerText = user.total_spent_display || 'Rs 0.00';
    document.getElementById('modalLastOrder').innerText = user.last_order_display || 'No orders yet';
    document.getElementById('modalAddress').innerText = user.latest_address_display || 'Not provided';
    document.getElementById('modalDate').innerText = user.created_at;

    const imageContainer = document.getElementById('modalImages');
    imageContainer.innerHTML = '';

    if (user.image) {
        const imagePath = user.image.startsWith('http') ? user.image : `../assets/images/${user.image}`;
        imageContainer.innerHTML = `<img src="${imagePath}" alt="User Image">`;
    } else {
        imageContainer.innerHTML = `<img src="../assets/images/avatar.jpg" alt="Default Image">`;
    }

    document.getElementById('userModal').style.display = 'block';
}

function closeOrderModal() {
    document.getElementById('userModal').style.display = 'none';
}

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#usertTable tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<script src="../design-assets/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
