<!-- resources/views/admin/settings/partials/fee-settings.blade.php -->
<div class="tab-pane fade" id="fee-settings" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-rupee-sign me-2"></i>Fee & Tax Settings</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Configure the platform fees, additional charges, and tax rates applied to bookings.
            </div>

            <h6 class="mt-4 mb-3 border-bottom pb-2">Platform Fee</h6>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="enable_platform_fee" name="enable_platform_fee" value="1" {{ $feeSettings['enable_platform_fee'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_platform_fee">Enable Platform Fee</label>
                    </div>

                    <label for="platform_fee" class="form-label">Platform Fee Amount (₹)</label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control" id="platform_fee" name="platform_fee" value="{{ $feeSettings['platform_fee'] }}" min="0" step="0.01">
                    </div>
                    <div class="form-text">Fixed platform fee applied to each booking.</div>
                </div>

                <div class="col-md-6">
                    <label for="fee_description" class="form-label">Fee Description</label>
                    <input type="text" class="form-control" id="fee_description" name="fee_description" value="{{ $feeSettings['fee_description'] }}">
                    <div class="form-text">Description shown to customers for this fee.</div>
                </div>
            </div>

            <h6 class="mt-4 mb-3 border-bottom pb-2">Additional Charges</h6>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="enable_other_charges" name="enable_other_charges" value="1" {{ $feeSettings['enable_other_charges'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_other_charges">Enable Other Charges</label>
                    </div>

                    <label for="other_charges_percentage" class="form-label">Other Charges Percentage</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="other_charges_percentage" name="other_charges_percentage" value="{{ $feeSettings['other_charges_percentage'] * 100 }}" min="0" max="100" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Additional percentage applied to booking price after discounts.</div>
                </div>
            </div>

            <h6 class="mt-4 mb-3 border-bottom pb-2">Tax Settings</h6>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="enable_gst" name="enable_gst" value="1" {{ $feeSettings['enable_gst'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="enable_gst">Enable GST</label>
                    </div>

                    <label for="gst_percentage" class="form-label">GST Percentage</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="gst_percentage" name="gst_percentage" value="{{ $feeSettings['gst_percentage'] * 100 }}" min="0" max="100" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">GST percentage applied to platform fee and other charges.</div>
                </div>
            </div>

            <div class="card mt-4 bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-calculator me-2"></i>Fee Calculation Example</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="example_booking_amount" class="form-label">Service Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="example_booking_amount" value="1000" min="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="example_home_visit_fee" class="form-label">Home Visit Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="example_home_visit_fee" value="0" min="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="example_discount" class="form-label">Discount Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="example_discount" value="0" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div id="fee_breakdown" class="mt-4">
                                <div>Service Price: <span id="base_amount">₹1000.00</span></div>
                                <div id="home_visit_fee_row" style="display:none">Home Visit Fee: <span id="home_visit_fee_calc">₹0.00</span></div>
                                <div id="discount_row" style="display:none">Discount Amount: <span id="discount_calc">₹0.00</span></div>
                                <div>Booking Price: <span id="booking_price_calc">₹1000.00</span></div>
                                <div>Platform Fee: <span id="platform_fee_calc">₹8.00</span></div>
                                <div>Other Charges (2%): <span id="other_charges_calc">₹20.00</span></div>
                                <div>Subtotal: <span id="subtotal">₹1028.00</span></div>
                                <div>GST (18%): <span id="gst_calc">₹5.04</span></div>
                                <div class="mt-2 fw-bold">Total Amount: <span id="total_amount">₹1033.04</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all input elements
        const exampleBookingAmountInput = document.getElementById('example_booking_amount');
        const exampleHomeVisitFeeInput = document.getElementById('example_home_visit_fee');
        const exampleDiscountInput = document.getElementById('example_discount');
        const platformFeeInput = document.getElementById('platform_fee');
        const otherChargesInput = document.getElementById('other_charges_percentage');
        const gstInput = document.getElementById('gst_percentage');
        const enablePlatformFeeCheckbox = document.getElementById('enable_platform_fee');
        const enableOtherChargesCheckbox = document.getElementById('enable_other_charges');
        const enableGstCheckbox = document.getElementById('enable_gst');

        // Calculator result elements
        const baseAmountElement = document.getElementById('base_amount');
        const homeVisitFeeElement = document.getElementById('home_visit_fee_calc');
        const homeVisitFeeRow = document.getElementById('home_visit_fee_row');
        const discountElement = document.getElementById('discount_calc');
        const discountRow = document.getElementById('discount_row');
        const bookingPriceElement = document.getElementById('booking_price_calc');
        const platformFeeElement = document.getElementById('platform_fee_calc');
        const otherChargesElement = document.getElementById('other_charges_calc');
        const subtotalElement = document.getElementById('subtotal');
        const gstElement = document.getElementById('gst_calc');
        const totalAmountElement = document.getElementById('total_amount');

        // Function to calculate fees
        function calculateFees() {
            // Get base values
            const bookingAmount = parseFloat(exampleBookingAmountInput.value) || 0;
            const homeVisitFee = parseFloat(exampleHomeVisitFeeInput.value) || 0;
            const discountAmount = parseFloat(exampleDiscountInput.value) || 0;

            // Get fee rates
            const platformFee = enablePlatformFeeCheckbox.checked ? parseFloat(platformFeeInput.value) || 0 : 0;
            const otherChargesPercentage = enableOtherChargesCheckbox.checked ? (parseFloat(otherChargesInput.value) || 0) / 100 : 0;
            const gstPercentage = enableGstCheckbox.checked ? (parseFloat(gstInput.value) || 0) / 100 : 0;

            // Calculate according to the formula
            // Original price + Home visit fee - Discount = Booking price
            const priceWithHomeVisit = bookingAmount + homeVisitFee;
            const bookingPriceAfterDiscount = Math.max(0, priceWithHomeVisit - discountAmount);

            // Calculate other charges (2% of booking price after discount)
            const otherCharges = Math.round(bookingPriceAfterDiscount * otherChargesPercentage * 100) / 100;

            // Calculate GST (18% of platform fee + other charges)
            const gstAmount = Math.round((platformFee + otherCharges) * gstPercentage * 100) / 100;

            // Calculate subtotal and total
            const subtotal = bookingPriceAfterDiscount + platformFee + otherCharges;
            const totalAmount = subtotal + gstAmount;

            // Update display
            baseAmountElement.textContent = '₹' + bookingAmount.toFixed(2);

            // Only show home visit fee if it's greater than 0
            if (homeVisitFee > 0) {
                homeVisitFeeRow.style.display = 'block';
                homeVisitFeeElement.textContent = '₹' + homeVisitFee.toFixed(2);
            } else {
                homeVisitFeeRow.style.display = 'none';
            }

            // Only show discount if it's greater than 0
            if (discountAmount > 0) {
                discountRow.style.display = 'block';
                discountElement.textContent = '₹' + discountAmount.toFixed(2);
            } else {
                discountRow.style.display = 'none';
            }

            bookingPriceElement.textContent = '₹' + bookingPriceAfterDiscount.toFixed(2);
            platformFeeElement.textContent = '₹' + platformFee.toFixed(2);
            otherChargesElement.textContent = '₹' + otherCharges.toFixed(2);
            subtotalElement.textContent = '₹' + subtotal.toFixed(2);
            gstElement.textContent = '₹' + gstAmount.toFixed(2);
            totalAmountElement.textContent = '₹' + totalAmount.toFixed(2);

            // Update percentage labels
            otherChargesElement.previousElementSibling.textContent =
                `Other Charges (${(otherChargesPercentage * 100).toFixed(1)}%):`;
            gstElement.previousElementSibling.textContent =
                `GST (${(gstPercentage * 100).toFixed(1)}%):`;
        }

        // Add event listeners to all inputs and toggles
        exampleBookingAmountInput.addEventListener('input', calculateFees);
        exampleHomeVisitFeeInput.addEventListener('input', calculateFees);
        exampleDiscountInput.addEventListener('input', calculateFees);
        platformFeeInput.addEventListener('input', calculateFees);
        otherChargesInput.addEventListener('input', calculateFees);
        gstInput.addEventListener('input', calculateFees);
        enablePlatformFeeCheckbox.addEventListener('change', calculateFees);
        enableOtherChargesCheckbox.addEventListener('change', calculateFees);
        enableGstCheckbox.addEventListener('change', calculateFees);

        // Initial calculation
        calculateFees();
    });
</script>
