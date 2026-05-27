<?php
include '../includes/header.php';

$sql = "SELECT pr.id, pr.product_id, pr.user_id, pr.review, pr.rating, 
               u.name AS customer_name, p.name AS product_name
        FROM product_ratings pr
        JOIN users u ON pr.user_id = u.id
        JOIN product p ON pr.product_id = p.id
        WHERE pr.deleted_at IS NULL
        ORDER BY pr.id DESC";

$res = mysqli_query($con, $sql);
?>

<style>
    .rating-table img {
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
        max-width: 500px;
        position: relative;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        animation: fadeIn 0.3s ease-in-out;
        text-align: center;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }

    @media (max-width: 768px) {
        .rating-table th, .rating-table td {
            font-size: 12px;
            padding: 8px;
        }
        .modal-content {
            width: 90%;
        }
    }
</style>

<div class="dashboard-content">
    <header class="page-header center-content text-center">
        <h1><i class="fas fa-star"></i> Product Ratings</h1>
    </header>

    <div class="search-wrapper">
        <input type="search" id="searchInput" placeholder="Search by Product ID, Product Name, Customer Name..." autocomplete="off" aria-label="Search ratings" />
        <button type="button" class="page-close-btn" title="Back to Dashboard" onclick="window.location.href='Admindashboard.php'">&times;</button>
    </div>

    <div class="table-container" role="region" aria-live="polite" aria-relevant="all">
        <table id="ratingTable" class="rating-table user-table" aria-label="Product Ratings Table">
            <thead>
                <tr>
                    <th>S.N</th>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Customer Name</th>
                    <th>Review</th>
                    <th>Rating</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($res && mysqli_num_rows($res) > 0):
                    $sn = 1;
                    while ($row = mysqli_fetch_assoc($res)):
                        $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                        ?>
                        <tr>
                            <td><?= $sn++ ?></td>
                            <td><?= htmlspecialchars($row['product_id']) ?></td>
                            <td><?= htmlspecialchars($row['product_name']) ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['review']) ?></td>
                            <td><?= htmlspecialchars($row['rating']) ?>/5</td>
                            <td class="text-center">
                                <div class="actions">
                                    <button class="btn view-btn" data-rating='<?= $jsonData ?>' onclick="viewRatingDetails(this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="product_rating_delete.php?id=<?= $row['id'] ?>" class="btn delete-btn" onclick="return confirm('Are you sure you want to delete this rating?');">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">No product ratings found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Rating View Modal -->
<div id="ratingModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeRatingModal()">&times;</span>
        <h2><i class="fas fa-star"></i> Rating Details</h2>
        <p><strong>Product Name:</strong> <span id="modalProductName"></span></p>
        <p><strong>Customer Name:</strong> <span id="modalCustomerName"></span></p>
        <p><strong>Review:</strong> <span id="modalReview"></span></p>
        <p><strong>Rating:</strong> <span id="modalRating"></span></p>
    </div>
</div>

<script>
// View Rating Details
function viewRatingDetails(button) {
    const ratingData = JSON.parse(button.getAttribute('data-rating'));
    document.getElementById('modalProductName').innerText = ratingData.product_name;
    document.getElementById('modalCustomerName').innerText = ratingData.customer_name;
    document.getElementById('modalReview').innerText = ratingData.review;
    document.getElementById('modalRating').innerText = ratingData.rating + ' / 5';
    document.getElementById('ratingModal').style.display = 'block';
}

function closeRatingModal() {
    document.getElementById('ratingModal').style.display = 'none';
}

// Search Functionality
document.getElementById('searchInput').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#ratingTable tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<script src="../design-assets/js/bootstrap.bundle.min.js"></script>
<?php include '../includes/footer.php'; ?>
