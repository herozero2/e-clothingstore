<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Userlogin.php?redirect=checkout.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/store.php';
$con = db_connect();
$user_id = (int) $_SESSION['user_id'];
$userResult = mysqli_query($con, "SELECT name, email FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($userResult);
$cart = $_SESSION['cart'] ?? [];
$grandTotal = 0;

foreach ($cart as $item) {
    $grandTotal += (float) $item['price'] * (int) $item['quantity'];
}
?>
<?php include("includes/header.php"); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIINfQd9QdgyfeByKsyNxBlBOtmFhHnPzHM=" crossorigin="">

<main class="container py-5" style="padding-top: 170px !important;">
    <div class="shop-toolbar mb-4">
        <div>
            <p class="text-primary fw-bold mb-1">Secure checkout</p>
            <h1 class="mb-0">Checkout</h1>
        </div>
        <a href="cart.php" class="btn btn-outline-secondary rounded-pill px-4">Back to Cart</a>
    </div>

    <?php if (!empty($_SESSION['checkout_error'])): ?>
        <div class="alert alert-warning">
            <?= htmlspecialchars($_SESSION['checkout_error']) ?>
        </div>
        <?php unset($_SESSION['checkout_error']); ?>
    <?php endif; ?>

    <?php if (empty($cart)): ?>
        <div class="checkout-panel text-center">
            <h2>Your cart is empty</h2>
            <p class="text-muted">Add products before checkout.</p>
            <a href="our_shop.php" class="btn btn-primary rounded-pill px-4">Shop Now</a>
        </div>
    <?php else: ?>
        <form action="place_order.php" method="POST">
            <div class="row g-4">
                <div class="col-lg-7">
                    <section class="checkout-panel">
                        <h3 class="mb-4">Shipping Details</h3>
                        <div class="row g-3">
                            <?php
                            $parts = explode(' ', trim($user['name'] ?? ''), 2);
                            $first = $parts[0] ?? '';
                            $last = $parts[1] ?? '';
                            ?>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">First Name</label>
                                <input class="form-control py-3" name="first_name" value="<?= htmlspecialchars($first) ?>" readonly required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Name</label>
                                <input class="form-control py-3" name="last_name" value="<?= htmlspecialchars($last) ?>" readonly required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Shipping Address</label>
                                <div class="input-group">
                                    <input class="form-control py-3" id="shipping_address" name="shipping_address" placeholder="Street, city, ward, district" required>
                                    <input type="hidden" id="shipping_lat" name="shipping_lat">
                                    <input type="hidden" id="shipping_lng" name="shipping_lng">
                                    <input type="hidden" id="shipping_location_details" name="shipping_location_details">
                                    <button class="btn btn-outline-primary" type="button" id="openMapPicker">
                                        <i class="fas fa-map-marker-alt"></i> Map
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Country</label>
                                <select class="form-select py-3" name="country" required>
                                    <option value="Nepal" selected>Nepal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input class="form-control py-3" type="tel" name="mobile" pattern="^\+?[0-9]{8,15}$" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold">Email</label>
                                <input class="form-control py-3" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly required>
                            </div>
                        </div>
                    </section>

                </div>

                <div class="col-lg-5">
                    <section class="checkout-panel">
                        <h3 class="mb-4">Order Summary</h3>
                        <?php foreach ($cart as $item): ?>
                            <div class="d-flex gap-3 align-items-center border-bottom py-3">
                                <img src="../assets/images/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: 8px;">
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <?php if (!empty($item['variant_label'])): ?>
                                        <div class="text-muted small"><?= htmlspecialchars($item['variant_label']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['variant_sku'])): ?>
                                        <div class="text-muted small">SKU: <?= htmlspecialchars($item['variant_sku']) ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted">Qty: <?= (int) $item['quantity'] ?></div>
                                </div>
                                <strong><?= money((float) $item['price'] * (int) $item['quantity'], $con) ?></strong>
                            </div>
                        <?php endforeach; ?>

                        <div class="mt-4">
                            <label class="form-label fw-bold">Shipping in Nepal</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="shipping_charge" id="shippingStandard" value="300" checked onchange="updateGrandTotal(300)">
                                <label class="form-check-label" for="shippingStandard">Standard Nepal Delivery: <?= money(300, $con) ?></label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="shipping_charge" id="shippingExpress" value="500" onchange="updateGrandTotal(500)">
                                <label class="form-check-label" for="shippingExpress">Express Nepal Delivery: <?= money(500, $con) ?></label>
                            </div>
                        </div>

                        <div class="border-top mt-4 pt-4">
                            <div class="d-flex justify-content-between"><span>Subtotal</span><strong><?= money($grandTotal, $con) ?></strong></div>
                            <div class="d-flex justify-content-between"><span>Shipping</span><strong id="shippingText"><?= money(300, $con) ?></strong></div>
                            <div class="d-flex justify-content-between fs-4 mt-3"><span>Total</span><strong id="grandTotalText"><?= money($grandTotal + 300, $con) ?></strong></div>
                        </div>

                        <div class="checkout-payment-card mt-4">
                            <h4 class="mb-3">Payment Gateway</h4>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="Cash on Delivery" checked required>
                                <span>
                                    <strong>Cash on Delivery</strong>
                                    <small>Pay when your order arrives.</small>
                                </span>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 mt-4">Place Order</button>
                    </section>
                </div>
            </div>
        </form>
    <?php endif; ?>
</main>

<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Choose Delivery Area</h5>
                <button type="button" class="btn-close" id="closeMapPicker" aria-label="Close"></button>
            </div>
            <div class="modal-body map-modal-body p-0">
                <div class="map-picker-toolbar">
                    <div>
                        <strong id="mapStatus">Click the map to choose your delivery location.</strong>
                        <small id="mapSelectedText">Nepal is selected by default. Zoom and click your exact delivery point.</small>
                    </div>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="useMapLocation" disabled>Use Location</button>
                </div>
                <div id="checkoutMap" class="checkout-map" aria-label="Delivery location map"></div>
                <div class="map-selected-card" id="mapDetailsPanel" hidden>
                    <strong>Selected Location Details</strong>
                    <dl id="mapDetailsList"></dl>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    const baseTotal = <?= json_encode($grandTotal) ?>;
    const currencySymbol = <?= json_encode(currency_symbol($con)) ?>;
    function updateGrandTotal(shipping) {
        document.getElementById('shippingText').innerText = currencySymbol + ' ' + Number(shipping).toFixed(2);
        document.getElementById('grandTotalText').innerText = currencySymbol + ' ' + (baseTotal + Number(shipping)).toFixed(2);
    }

    (function setupCheckoutMap() {
        const mapEl = document.getElementById('checkoutMap');
        const mapModal = document.getElementById('mapModal');
        const openMapButton = document.getElementById('openMapPicker');
        const closeMapButton = document.getElementById('closeMapPicker');
        const addressInput = document.getElementById('shipping_address');
        const latInput = document.getElementById('shipping_lat');
        const lngInput = document.getElementById('shipping_lng');
        const detailsInput = document.getElementById('shipping_location_details');
        const useButton = document.getElementById('useMapLocation');
        const statusEl = document.getElementById('mapStatus');
        const selectedText = document.getElementById('mapSelectedText');
        const detailsPanel = document.getElementById('mapDetailsPanel');
        const detailsList = document.getElementById('mapDetailsList');

        if (!mapEl || typeof L === 'undefined') {
            if (statusEl) {
                statusEl.textContent = 'Map could not load. Please type your delivery address manually.';
            }
            return;
        }

        let checkoutMap = null;
        let marker = null;
        let selectedLocation = null;
        const nepalBounds = [[26.25, 80.05], [30.45, 88.25]];
        const deliveryMarkerIcon = L.divIcon({
            className: 'checkout-map-marker',
            html: '<span aria-hidden="true"></span>',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
        });

        function openMapModal() {
            mapModal.classList.add('show');
            mapModal.style.display = 'block';
            mapModal.removeAttribute('aria-hidden');
            mapModal.setAttribute('aria-modal', 'true');
            document.body.classList.add('modal-open');
            document.body.style.overflow = 'hidden';

            if (!document.querySelector('.map-modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show map-modal-backdrop';
                document.body.appendChild(backdrop);
            }

            initMap();
            setTimeout(() => checkoutMap?.invalidateSize(), 250);
        }

        function closeMapModal() {
            mapModal.classList.remove('show');
            mapModal.style.display = 'none';
            mapModal.setAttribute('aria-hidden', 'true');
            mapModal.removeAttribute('aria-modal');
            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.map-modal-backdrop, .modal-backdrop').forEach(backdrop => backdrop.remove());
        }

        function isInsideNepal(lat, lng) {
            return lat >= nepalBounds[0][0] && lat <= nepalBounds[1][0] && lng >= nepalBounds[0][1] && lng <= nepalBounds[1][1];
        }

        function uniqueParts(parts) {
            const seen = new Set();
            return parts
                .map(part => (part || '').toString().trim())
                .filter(part => {
                    const key = part.toLowerCase();
                    if (!part || seen.has(key)) {
                        return false;
                    }
                    seen.add(key);
                    return true;
                });
        }

        function composeDetails(data, lat, lng) {
            const address = data?.address || {};
            const named = data?.namedetails || {};
            const name = named.name || data?.name || address.amenity || address.building || address.road || address.neighbourhood || address.suburb || address.village || address.town || address.city || 'Selected delivery point';
            const roadLine = uniqueParts([address.house_number, address.road]).join(' ');
            const area = address.neighbourhood || address.suburb || address.quarter || address.hamlet || address.residential || '';
            const city = address.city || address.town || address.village || address.municipality || '';
            const district = address.county || address.district || address.city_district || '';
            const province = address.state || address.region || '';
            const postalCode = address.postcode || 'Not available for this exact point';
            const country = address.country || 'Nepal';
            const coordinates = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
            const line = uniqueParts([
                name,
                roadLine,
                area,
                city,
                district,
                province,
                address.postcode ? `ZIP/Postal code ${address.postcode}` : '',
                country,
            ]).join(', ');

            return {
                label: line || `Selected delivery point, Nepal, Coordinates ${coordinates}`,
                details: {
                    'Location name': name,
                    'Street/Road': roadLine || 'Not available',
                    'Area': area || 'Not available',
                    'City/Town': city || 'Not available',
                    'District': district || 'Not available',
                    'Province/State': province || 'Not available',
                    'ZIP/Postal code': postalCode,
                    'Country': country,
                    'Latitude': lat.toFixed(6),
                    'Longitude': lng.toFixed(6),
                },
            };
        }

        async function lookupAddress(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&namedetails=1&zoom=18&lat=${lat}&lon=${lng}`);
                if (!response.ok) {
                    throw new Error('Reverse geocoding failed');
                }
                const data = await response.json();
                return composeDetails(data, lat, lng);
            } catch (error) {
                return composeDetails(null, lat, lng);
            }
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function renderDetails(details) {
            if (!detailsList || !detailsPanel) {
                return;
            }

            detailsList.innerHTML = Object.entries(details)
                .map(([term, value]) => `<div><dt>${escapeHtml(term)}</dt><dd>${escapeHtml(value)}</dd></div>`)
                .join('');
            detailsPanel.hidden = false;
        }

        function setSelectedLocation(lat, lng, location) {
            selectedLocation = { lat, lng, label: location.label, details: location.details };
            latInput.value = lat.toFixed(6);
            lngInput.value = lng.toFixed(6);
            detailsInput.value = Object.entries(location.details)
                .map(([term, value]) => `${term}: ${value}`)
                .join(' | ');
            selectedText.textContent = location.label;
            renderDetails(location.details);
            useButton.disabled = false;
        }

        function useSelectedLocation() {
            if (!selectedLocation) {
                return;
            }

            addressInput.value = selectedLocation.label;
            latInput.value = selectedLocation.lat.toFixed(6);
            lngInput.value = selectedLocation.lng.toFixed(6);
            detailsInput.value = Object.entries(selectedLocation.details)
                .map(([term, value]) => `${term}: ${value}`)
                .join(' | ');
            addressInput.dispatchEvent(new Event('input', { bubbles: true }));

            closeMapModal();
        }

        function initMap() {
            if (checkoutMap) {
                setTimeout(() => checkoutMap.invalidateSize(), 180);
                return;
            }

            checkoutMap = L.map('checkoutMap', {
                scrollWheelZoom: true,
                zoomControl: true,
                maxBounds: L.latLngBounds(nepalBounds).pad(0.15),
                maxBoundsViscosity: 0.85,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(checkoutMap);

            checkoutMap.fitBounds(nepalBounds);

            checkoutMap.on('click', async function (event) {
                const { lat, lng } = event.latlng;
                if (!isInsideNepal(lat, lng)) {
                    statusEl.textContent = 'Please choose a delivery location inside Nepal.';
                    selectedText.textContent = 'The checkout country is Nepal, so the selected point must be inside Nepal.';
                    useButton.disabled = true;
                    return;
                }

                if (!marker) {
                    marker = L.marker([lat, lng], { draggable: true, icon: deliveryMarkerIcon }).addTo(checkoutMap);
                    marker.on('dragend', async function () {
                        const point = marker.getLatLng();
                        if (!isInsideNepal(point.lat, point.lng)) {
                            statusEl.textContent = 'Please keep the marker inside Nepal.';
                            selectedText.textContent = 'The checkout country is Nepal, so the selected point must be inside Nepal.';
                            useButton.disabled = true;
                            return;
                        }

                        statusEl.textContent = 'Finding address for selected location...';
                        const label = await lookupAddress(point.lat, point.lng);
                        setSelectedLocation(point.lat, point.lng, label);
                        statusEl.textContent = 'Delivery location selected.';
                    });
                } else {
                    marker.setLatLng([lat, lng]);
                }

                statusEl.textContent = 'Finding address for selected location...';
                const location = await lookupAddress(lat, lng);
                setSelectedLocation(lat, lng, location);
                statusEl.textContent = 'Delivery location selected.';
            });
        }

        openMapButton?.addEventListener('click', function (event) {
            event.preventDefault();
            openMapModal();
        });

        closeMapButton?.addEventListener('click', function () {
            closeMapModal();
        });

        mapModal?.addEventListener('click', function (event) {
            if (event.target === mapModal) {
                closeMapModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && mapModal.classList.contains('show')) {
                closeMapModal();
            }
        });

        useButton?.addEventListener('click', function (event) {
            event.preventDefault();
            useSelectedLocation();
        });
    })();
</script>

<?php include("includes/footer.php"); ?>
