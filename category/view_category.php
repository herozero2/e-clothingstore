<?php
include '../includes/header.php';

// Fetch categories where deleted_at IS NULL
$result = mysqli_query($con, "SELECT * FROM category WHERE deleted_at IS NULL ORDER BY created_at ASC");
$categories = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="../assets/css/view_category.css">

<div class="dashboard-content">
    <header class="page-header center-content text-center">
        <h1><i class="fas fa-list"></i> Categories</h1>
    </header>

    <!-- Search Bar -->
    <div class="search-wrapper">
        <input type="search" id="searchInput" placeholder="Search by ID or Name..." autocomplete="off" aria-label="Search categories" />
       
        <button type="button" class="page-close-btn" title="Back to Dashboard" aria-label="Close and return to dashboard" onclick="window.location.href='../admin/Admindashboard.php'">
            &times;
        </button>
    </div>

    <div class="table-container" role="region" aria-live="polite" aria-relevant="all">
        <table id="categoryTable" class="category-table" aria-label="List of categories">
            <thead>
                <tr>
                    <th>S.N.</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Created At</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0):
                    $sn = 1;
                    foreach ($categories as $cat): ?>
                    <tr data-id="<?= $cat['id'] ?>" data-name="<?= htmlspecialchars(strtolower($cat['name'])) ?>">
                        <td><?= $sn++ ?></td>
                        <td><?= htmlspecialchars($cat['name']) ?></td>
                        <td><?= htmlspecialchars(strlen($cat['description']) > 80 ? substr($cat['description'], 0, 80) . '...' : $cat['description']) ?></td>
                        <td><?= date("Y-m-d H:i", strtotime($cat['created_at'])) ?></td>
                        <td class="text-center">
                            <div class="actions">
                                <!-- View Button -->
                                <button class="btn view-btn" aria-label="View <?= htmlspecialchars($cat['name']) ?>"
                                    onclick='openCategoryModal(<?= json_encode($cat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="fas fa-eye"></i>
                                </button>

                                <!-- Edit Button -->
                                <a href="edit_category.php?id=<?= $cat['id'] ?>" class="btn edit-btn" aria-label="Edit <?= htmlspecialchars($cat['name']) ?>">
                                    <i class="fas fa-edit"></i>
                                </a>

                                <!-- Delete Button -->
                                <a href="delete_category.php?id=<?= $cat['id'] ?>" class="btn delete-btn" aria-label="Delete <?= htmlspecialchars($cat['name']) ?>"
                                   onclick="return confirm('Are you sure you want to delete this category?');">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="no-data">No categories found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Category Modal -->
<div id="categoryModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modalCategoryName" style="display:none;">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Close category details" onclick="closeCategoryModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="modal-body">
            <div class="modal-details">
                <h2 id="modalCategoryName"></h2>
                <p id="modalCategoryDesc" class="desc-font"></p>
                <p><strong>Created At:</strong> <span id="modalCategoryCreated"></span></p>
            </div>
        </div>
    </div>
</div>
</div>
<?php include '../includes/footer.php'; ?>

<script>

// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#categoryTable tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});


function filterTable(query) {
    const q = query.trim().toLowerCase();
    for (let row of tbodyRows) {
        const id = row.getAttribute('data-id');
        const name = row.getAttribute('data-name');

        if (id.includes(q) || name.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Modal functionality
const categoryModal = document.getElementById('categoryModal');
const modalCategoryName = document.getElementById('modalCategoryName');
const modalCategoryDesc = document.getElementById('modalCategoryDesc');
const modalCategoryCreated = document.getElementById('modalCategoryCreated');

function openCategoryModal(category) {
    modalCategoryName.textContent = category.name;
    modalCategoryDesc.textContent = category.description;
    modalCategoryCreated.textContent = category.created_at;
    categoryModal.style.display = 'flex';
    modalCategoryName.focus();
}

function closeCategoryModal() {
    categoryModal.style.display = 'none';
}
</script>