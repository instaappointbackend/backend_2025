<!-- resources/views/admin/settings/partials/commission.blade.php -->
<div class="tab-pane fade" id="commission-settings" role="tabpanel">
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Commission Settings</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="commission_percentage" class="form-label">Commission Percentage</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="commission_percentage" name="commission_percentage" value="{{ $commissionSettings['commission_percentage'] }}" min="0" max="100" step="0.01">
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">Percentage of service price charged as commission.</div>
                </div>

<!--                <div class="col-md-6">-->
<!--                    <label for="minimum_payout_amount" class="form-label">Minimum Payout Amount</label>-->
<!--                    <div class="input-group">-->
<!--                        <span class="input-group-text">₹</span>-->
<!--                        <input type="number" class="form-control" id="minimum_payout_amount" name="minimum_payout_amount" value="{{ $commissionSettings['minimum_payout_amount'] }}" min="0" step="0.01">-->
<!--                    </div>-->
<!--                    <div class="form-text">Minimum amount required for vendor payout.</div>-->
<!--                </div>-->
            </div>

<!--            <div class="row mb-3">-->
<!--                <div class="col-md-6">-->
<!--                    <label for="payout_schedule" class="form-label">Payout Schedule</label>-->
<!--                    <select class="form-select" id="payout_schedule" name="payout_schedule">-->
<!--                        <option value="weekly" {{ $commissionSettings['payout_schedule'] == 'weekly' ? 'selected' : '' }}>Weekly</option>-->
<!--                        <option value="bi-weekly" {{ $commissionSettings['payout_schedule'] == 'bi-weekly' ? 'selected' : '' }}>Bi-Weekly</option>-->
<!--                        <option value="monthly" {{ $commissionSettings['payout_schedule'] == 'monthly' ? 'selected' : '' }}>Monthly</option>-->
<!--                    </select>-->
<!--                </div>-->
<!--                -->
<!--                <!-- <div class="col-md-6">-->
<!--                    <div class="form-check form-switch mt-4">-->
<!--                        <input class="form-check-input" type="checkbox" role="switch" id="enable_automatic_payouts" name="enable_automatic_payouts" value="1" {{ $commissionSettings['enable_automatic_payouts'] ? 'checked' : '' }}>-->
<!--                        <label class="form-check-label" for="enable_automatic_payouts">Enable Automatic Payouts</label>-->
<!--                    </div>-->
<!--                </div> -->
<!--            </div>-->
        </div>
    </div>
</div>
