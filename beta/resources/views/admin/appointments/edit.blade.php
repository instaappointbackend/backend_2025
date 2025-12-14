<!-- resources/views/admin/appointments/edit.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Edit Appointment')

@section('page-title', 'Edit Appointment')

@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb m-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.appointments.index') }}">Appointments</a></li>
        <li class="breadcrumb-item active" aria-current="page">Edit Appointment #{{ $appointment->id }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Appointment Details</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.appointments.update', $appointment->id) }}" method="POST" id="appointmentForm">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <!-- Customer Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="client_id" class="form-label">Customer <span class="text-danger">*</span></label>
                            <select class="form-select @error('client_id') is-invalid @enderror" id="client_id" name="client_id" required>
                                <option value="">-- Select Customer --</option>
                                @foreach($clients as $client)
                                <option value="{{ $client->id }}" {{ old('client_id', $appointment->client_id) == $client->id ? 'selected' : '' }}>
                                {{ $client->name }} ({{ $client->mobile }})
                                </option>
                                @endforeach
                            </select>
                            @error('client_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Vendor Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="user_id" class="form-label">Service Provider <span class="text-danger">*</span></label>
                            <select class="form-select @error('user_id') is-invalid @enderror" id="user_id" name="user_id" required>
                                <option value="">-- Select Provider --</option>
                                @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ old('user_id', $appointment->user_id) == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->name }} ({{ $vendor->businessCategory ? $vendor->businessCategory->name : 'No Category' }})
                                </option>
                                @endforeach
                            </select>
                            @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Service Type Selection -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Service Type <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="service_type" id="service_type_single" value="single"
                                       {{ (!$appointment->combo_service_id || $appointment->service_id) ? 'checked' : '' }} autocomplete="off">
                                <label class="btn btn-outline-primary" for="service_type_single">Single Service</label>

                                <input type="radio" class="btn-check" name="service_type" id="service_type_combo" value="combo"
                                       {{ $appointment->combo_service_id ? 'checked' : '' }} autocomplete="off">
                                <label class="btn btn-outline-primary" for="service_type_combo">Combo Service</label>
                            </div>
                        </div>

                        <!-- Single Service Selection -->
                        <div class="col-md-12 mb-3 service-selection" id="single_service_container"
                             style="{{ (!$appointment->combo_service_id || $appointment->service_id) ? '' : 'display: none;' }}">
                            <label for="service_id" class="form-label">Select Service <span class="text-danger">*</span></label>
                            <select class="form-select @error('service_id') is-invalid @enderror" id="service_id" name="service_id">
                                <option value="">-- Select Service --</option>
                                @foreach($services as $service)
                                <option value="{{ $service->id }}"
                                        data-price="{{ $service->price }}"
                                        data-duration="{{ $service->duration }}"
                                        {{ old('service_id', $appointment->service_id) == $service->id ? 'selected' : '' }}>
                                {{ $service->name }} - ₹{{ number_format($service->price, 2) }} ({{ $service->duration }} min)
                                </option>
                                @endforeach
                            </select>
                            @error('service_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Combo Service Selection -->
                        <div class="col-md-12 mb-3 service-selection" id="combo_service_container"
                             style="{{ $appointment->combo_service_id ? '' : 'display: none;' }}">
                            <label for="combo_service_id" class="form-label">Select Combo Service <span class="text-danger">*</span></label>
                            <select class="form-select @error('combo_service_id') is-invalid @enderror" id="combo_service_id" name="combo_service_id">
                                <option value="">-- Select Combo Service --</option>
                                @php $comboServices = \App\Models\ComboService::where('is_active', 1)->get(); @endphp
                                @foreach($comboServices as $comboService)
                                <option value="{{ $comboService->id }}"
                                        data-price="{{ $comboService->discounted_price }}"
                                        data-duration="{{ $comboService->total_duration }}"
                                        {{ old('combo_service_id', $appointment->combo_service_id) == $comboService->id ? 'selected' : '' }}>
                                {{ $comboService->name }} - ₹{{ number_format($comboService->discounted_price, 2) }}
                                ({{ $comboService->total_duration }} min)
                                </option>
                                @endforeach
                            </select>
                            @error('combo_service_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Date and Time Fields -->
                        <div class="col-md-4 mb-3">
                            <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" id="date" name="date"
                                   value="{{ old('date', $appointment->date->format('Y-m-d')) }}" required>
                            @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('start_time') is-invalid @enderror" id="start_time" name="start_time"
                                   value="{{ old('start_time', $appointment->start_time->format('H:i')) }}" required>
                            @error('start_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control @error('end_time') is-invalid @enderror" id="end_time" name="end_time"
                                   value="{{ old('end_time', $appointment->end_time->format('H:i')) }}" required>
                            @error('end_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Visit Type -->
                        <div class="col-md-6 mb-3">
                            <label for="visit_type" class="form-label">Visit Type</label>
                            <select class="form-select @error('visit_type') is-invalid @enderror" id="visit_type" name="visit_type">
                                <option value="office" {{ old('visit_type', $appointment->visit_type) == 'office' ? 'selected' : '' }}>Office Visit</option>
                                <option value="home" {{ old('visit_type', $appointment->visit_type) == 'home' ? 'selected' : '' }}>Home Visit</option>
                                <option value="virtual" {{ old('visit_type', $appointment->visit_type) == 'virtual' ? 'selected' : '' }}>Virtual</option>
                            </select>
                            @error('visit_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status Selection -->
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="{{ \App\Models\Appointment::STATUS_PENDING }}"
                                        {{ old('status', $appointment->status) == \App\Models\Appointment::STATUS_PENDING ? 'selected' : '' }}>
                                Pending
                                </option>
                                <option value="{{ \App\Models\Appointment::STATUS_CONFIRMED }}"
                                        {{ old('status', $appointment->status) == \App\Models\Appointment::STATUS_CONFIRMED ? 'selected' : '' }}>
                                Confirmed
                                </option>
                                <option value="{{ \App\Models\Appointment::STATUS_COMPLETED }}"
                                        {{ old('status', $appointment->status) == \App\Models\Appointment::STATUS_COMPLETED ? 'selected' : '' }}>
                                Completed
                                </option>
                                <option value="{{ \App\Models\Appointment::STATUS_CANCELLED }}"
                                        {{ old('status', $appointment->status) == \App\Models\Appointment::STATUS_CANCELLED ? 'selected' : '' }}>
                                Cancelled
                                </option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12 mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $appointment->notes) }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-actions text-end mt-3">
                        <a href="{{ route('admin.appointments.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Payment Details</h5>
            </div>
            <div class="card-body">
                <div id="payment-form">
                    <div class="mb-3">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select class="form-select" id="payment_status" name="payment_status" form="appointmentForm">
                            <option value="">-- Select Payment Status --</option>
                            <option value="{{ \App\Models\Appointment::PAYMENT_STATUS_PENDING }}"
                                    {{ old('payment_status', $appointment->payment_status) == \App\Models\Appointment::PAYMENT_STATUS_PENDING ? 'selected' : '' }}>
                            Pending
                            </option>
                            <option value="{{ \App\Models\Appointment::PAYMENT_STATUS_PAID }}"
                                    {{ old('payment_status', $appointment->payment_status) == \App\Models\Appointment::PAYMENT_STATUS_PAID ? 'selected' : '' }}>
                            Paid
                            </option>
                            <option value="{{ \App\Models\Appointment::PAYMENT_STATUS_FAILED }}"
                                    {{ old('payment_status', $appointment->payment_status) == \App\Models\Appointment::PAYMENT_STATUS_FAILED ? 'selected' : '' }}>
                            Failed
                            </option>
                            <option value="{{ \App\Models\Appointment::PAYMENT_STATUS_REFUNDED }}"
                                    {{ old('payment_status', $appointment->payment_status) == \App\Models\Appointment::PAYMENT_STATUS_REFUNDED ? 'selected' : '' }}>
                            Refunded
                            </option>
                        </select>
                    </div>

                    <div class="mb-3 payment-details" id="payment_method_container" style="{{ $appointment->payment_status ? '' : 'display: none;' }}">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" form="appointmentForm">
                            <option value="">-- Select Payment Method --</option>
                            <option value="cash" {{ old('payment_method', $appointment->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="card" {{ old('payment_method', $appointment->payment_method) == 'card' ? 'selected' : '' }}>Credit/Debit Card</option>
                            <option value="upi" {{ old('payment_method', $appointment->payment_method) == 'upi' ? 'selected' : '' }}>UPI</option>
                            <option value="phonepe" {{ old('payment_method', $appointment->payment_method) == 'phonepe' ? 'selected' : '' }}>PhonePe</option>
                            <option value="wallet" {{ old('payment_method', $appointment->payment_method) == 'wallet' ? 'selected' : '' }}>Wallet</option>
                            <option value="netbanking" {{ old('payment_method', $appointment->payment_method) == 'netbanking' ? 'selected' : '' }}>Net Banking</option>
                        </select>
                    </div>

                    <div class="mb-3 payment-details" id="payment_amount_container" style="{{ $appointment->payment_status ? '' : 'display: none;' }}">
                        <label for="payment_amount" class="form-label">Payment Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" class="form-control" id="payment_amount" name="payment_amount"
                                   value="{{ old('payment_amount', $appointment->payment_amount) }}" form="appointmentForm">
                        </div>
                    </div>

                    <div class="price-calculation mt-4 border rounded p-3" style="{{ $appointment->payment_status ? '' : 'display: none;' }}">
                        <h6 class="mb-3">Price Calculation</h6>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Base Price:</span>
                            <span id="basePrice">₹{{ number_format($appointment->original_price ?: 0, 2) }}</span>
                        </div>

                        @if($appointment->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span>Discount ({{ $appointment->discount_percentage }}%):</span>
                            <span>-₹{{ number_format($appointment->discount_amount, 2) }}</span>
                        </div>
                        @endif

                        @if($appointment->home_visit_fee > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Home Visit Fee:</span>
                            <span>+₹{{ number_format($appointment->home_visit_fee, 2) }}</span>
                        </div>
                        @endif

                        @if($appointment->platform_fees > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>Platform Fee:</span>
                            <span>+₹{{ number_format($appointment->platform_fees, 2) }}</span>
                        </div>
                        @endif

                        @if($appointment->gst > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span>GST:</span>
                            <span>+₹{{ number_format($appointment->gst, 2) }}</span>
                        </div>
                        @endif

                        <hr>

                        <div class="d-flex justify-content-between fw-bold">
                            <span>Total Amount:</span>
                            <span id="totalPrice">₹{{ number_format($appointment->payment_amount ?: 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Current Status</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Appointment Status</label>
                        <div>
                            <span class="badge {{ $appointment->status == 'pending' ? 'bg-warning' :
                                             ($appointment->status == 'confirmed' ? 'bg-primary' :
                                             ($appointment->status == 'completed' ? 'bg-success' :
                                             ($appointment->status == 'cancelled' ? 'bg-danger' : 'bg-secondary'))) }} px-3 py-2">
                                {{ ucfirst($appointment->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Payment Status</label>
                        <div>
                            <span class="badge {{ !$appointment->payment_status ? 'bg-secondary' :
                                             ($appointment->payment_status == 'pending' ? 'bg-warning' :
                                             ($appointment->payment_status == 'paid' ? 'bg-success' :
                                             ($appointment->payment_status == 'failed' ? 'bg-danger' :
                                             ($appointment->payment_status == 'refunded' ? 'bg-info' : 'bg-secondary')))) }} px-3 py-2">
                                {{ $appointment->payment_status ? ucfirst($appointment->payment_status) : 'Not Set' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label text-muted">Quick Actions</label>
                    <div class="d-flex flex-wrap gap-2">
                        @if($appointment->status != 'confirmed')
                        <form action="{{ route('admin.appointments.status', ['appointment' => $appointment->id, 'status' => 'confirmed']) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-primary">Confirm</button>
                        </form>
                        @endif

                        @if($appointment->status != 'completed')
                        <form action="{{ route('admin.appointments.status', ['appointment' => $appointment->id, 'status' => 'completed']) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-success">Complete</button>
                        </form>
                        @endif

                        @if($appointment->status != 'cancelled')
                        <form action="{{ route('admin.appointments.status', ['appointment' => $appointment->id, 'status' => 'cancelled']) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Service Type Selection
        const serviceTypeRadios = document.querySelectorAll('input[name="service_type"]');
        const singleServiceContainer = document.getElementById('single_service_container');
        const comboServiceContainer = document.getElementById('combo_service_container');
        const serviceIdSelect = document.getElementById('service_id');
        const comboServiceIdSelect = document.getElementById('combo_service_id');

        serviceTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'single') {
                    singleServiceContainer.style.display = '';
                    comboServiceContainer.style.display = 'none';
                    comboServiceIdSelect.value = '';
                    serviceIdSelect.setAttribute('required', 'required');
                    comboServiceIdSelect.removeAttribute('required');
                } else {
                    singleServiceContainer.style.display = 'none';
                    comboServiceContainer.style.display = '';
                    serviceIdSelect.value = '';
                    comboServiceIdSelect.setAttribute('required', 'required');
                    serviceIdSelect.removeAttribute('required');
                }
                updateEndTime();
            });
        });

        // Payment Status
        const paymentStatusSelect = document.getElementById('payment_status');
        const paymentDetailsContainers = document.querySelectorAll('.payment-details');

        paymentStatusSelect.addEventListener('change', function() {
            if (this.value) {
                paymentDetailsContainers.forEach(container => {
                    container.style.display = '';
                });
                document.querySelector('.price-calculation').style.display = '';

                // If payment status is 'paid', make payment method and amount required
                if (this.value === '{{ \App\Models\Appointment::PAYMENT_STATUS_PAID }}') {
                    document.getElementById('payment_method').setAttribute('required', 'required');
                    document.getElementById('payment_amount').setAttribute('required', 'required');
                } else {
                    document.getElementById('payment_method').removeAttribute('required');
                    document.getElementById('payment_amount').removeAttribute('required');
                }
            } else {
                paymentDetailsContainers.forEach(container => {
                    container.style.display = 'none';
                });
                document.querySelector('.price-calculation').style.display = 'none';
                document.getElementById('payment_method').removeAttribute('required');
                document.getElementById('payment_amount').removeAttribute('required');
            }
        });

        // Service/Combo Selection - Update price and end time
        function updateEndTime() {
            let duration = 0;
            let price = 0;

            if (document.getElementById('service_type_single').checked && serviceIdSelect.value) {
                const selectedOption = serviceIdSelect.options[serviceIdSelect.selectedIndex];
                duration = parseInt(selectedOption.getAttribute('data-duration') || 0);
                price = parseFloat(selectedOption.getAttribute('data-price') || 0);
            } else if (document.getElementById('service_type_combo').checked && comboServiceIdSelect.value) {
                const selectedOption = comboServiceIdSelect.options[comboServiceIdSelect.selectedIndex];
                duration = parseInt(selectedOption.getAttribute('data-duration') || 0);
                price = parseFloat(selectedOption.getAttribute('data-price') || 0);
            }

            // Update price display
            if (price > 0) {
                document.getElementById('basePrice').textContent = '₹' + price.toFixed(2);
                document.getElementById('totalPrice').textContent = '₹' + price.toFixed(2);
                document.getElementById('payment_amount').value = price.toFixed(2);
            }

            // Calculate end time based on start time and duration
            if (duration > 0 && document.getElementById('start_time').value) {
                const startTime = document.getElementById('start_time').value;
                const [hours, minutes] = startTime.split(':').map(Number);

                let endTimeDate = new Date();
                endTimeDate.setHours(hours, minutes, 0, 0);
                endTimeDate.setMinutes(endTimeDate.getMinutes() + duration);

                const endHours = endTimeDate.getHours().toString().padStart(2, '0');
                const endMinutes = endTimeDate.getMinutes().toString().padStart(2, '0');
                document.getElementById('end_time').value = `${endHours}:${endMinutes}`;
            }
        }

        // Listen for changes to update end time
        serviceIdSelect.addEventListener('change', updateEndTime);
        comboServiceIdSelect.addEventListener('change', updateEndTime);
        document.getElementById('start_time').addEventListener('change', updateEndTime);

        // Initialize payment fields visibility
        if (paymentStatusSelect.value) {
            paymentDetailsContainers.forEach(container => {
                container.style.display = '';
            });
            document.querySelector('.price-calculation').style.display = '';
        } else {
            paymentDetailsContainers.forEach(container => {
                container.style.display = 'none';
            });
            document.querySelector('.price-calculation').style.display = 'none';
        }
    });
</script>
@endsection
