<!-- resources/views/admin/appointments/calendar.blade.php -->
@extends('admin.layouts.app')

@section('title', 'Appointment Calendar')

@section('page-title', 'Appointment Calendar')

@section('styles')
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
<style>
    .fc-event {
        cursor: pointer;
    }
    .appointment-info {
        font-size: 14px;
    }
    .appointment-info .label {
        font-weight: 600;
        display: inline-block;
        min-width: 80px;
    }
    .fc .fc-button-primary {
        background-color: #5e72e4;
        border-color: #5e72e4;
    }
    .fc .fc-button-primary:hover {
        background-color: #4a5cd0;
        border-color: #3f51c8;
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background-color: #3f51c8;
        border-color: #3a4bb8;
    }
    .fc-daygrid-day.fc-day-today {
        background-color: rgba(94, 114, 228, 0.1);
    }
    .fc-daygrid-event-dot {
        display: none;
    }
    .fc-event-time {
        font-weight: 600;
    }
    .fc-daygrid-event {
        border-radius: 4px;
        padding: 2px 4px;
    }
</style>
@endsection

@section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.appointments.index') }}">Appointments</a></li>
            <li class="breadcrumb-item active" aria-current="page">Calendar</li>
        </ol>
    </nav>
@endsection

@section('page-actions')
    <a href="{{ route('admin.appointments.index') }}" class="btn btn-info me-2">
        <i class="fas fa-list me-1"></i> List View
    </a>

@endsection

@section('content')
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="calendar-vendor" class="form-label">Vendor</label>
                        <select id="calendar-vendor" class="form-select">
                            <option value="">All Vendors</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="calendar-status" class="form-label">Status</label>
                        <select id="calendar-status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <div class="d-grid">
                        <button id="apply-filters" class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Legend</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2" style="width: 20px; height: 20px; background-color: #fb6340; border-radius: 4px;"></div>
                        <div>Pending</div>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2" style="width: 20px; height: 20px; background-color: #5e72e4; border-radius: 4px;"></div>
                        <div>Confirmed</div>
                    </div>
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2" style="width: 20px; height: 20px; background-color: #2dce89; border-radius: 4px;"></div>
                        <div>Completed</div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="me-2" style="width: 20px; height: 20px; background-color: #f5365c; border-radius: 4px;"></div>
                        <div>Cancelled</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Appointment Modal -->
    <div class="modal fade" id="createAppointmentModal" tabindex="-1" aria-labelledby="createAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="{{ route('admin.appointments.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="createAppointmentModalLabel">Create New Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="user_id" class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select class="form-select" id="user_id" name="user_id" required>
                                    <option value="">Select Vendor</option>
                                    @foreach($vendors as $vendor)
                                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="client_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Select Customer</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="service_id" class="form-label">Service <span class="text-danger">*</span></label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Select Service</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}" data-duration="{{ $service->duration }}">{{ $service->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="date" name="date" required min="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>

                            <div class="col-md-6">
                                <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Appointment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Appointment Modal -->
    <div class="modal fade" id="viewAppointmentModal" tabindex="-1" aria-labelledby="viewAppointmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewAppointmentModalLabel">Appointment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="appointment-details">
                        <div class="text-center mb-3">
                            <div id="appointment-status" class="badge bg-primary mb-2">Status</div>
                            <h5 id="appointment-service">Service Name</h5>
                        </div>

                        <div class="appointment-info mb-2">
                            <span class="label">Date:</span>
                            <span id="appointment-date">Date</span>
                        </div>

                        <div class="appointment-info mb-2">
                            <span class="label">Time:</span>
                            <span id="appointment-time">Time</span>
                        </div>

                        <div class="appointment-info mb-2">
                            <span class="label">Customer:</span>
                            <span id="appointment-client">Customer Name</span>
                        </div>

                        <div class="appointment-info mb-2">
                            <span class="label">Vendor:</span>
                            <span id="appointment-vendor">Vendor Name</span>
                        </div>

                        <div class="appointment-info mb-3">
                            <span class="label">Created:</span>
                            <span id="appointment-created">Created Date</span>
                        </div>

                        <div id="appointment-notes-section" class="mb-3">
                            <h6>Notes</h6>
                            <p id="appointment-notes" class="p-2 bg-light rounded">Notes</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
<!--                    <a id="appointment-edit-link" href="#" class="btn btn-primary">Edit</a>-->
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize FullCalendar
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: @json($appointments),
            eventClick: function(info) {
                // Open the appointment view modal with details
                showAppointmentDetails(info.event);
            },
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                meridiem: 'short'
            },
            dayMaxEvents: true, // Allow "more" link when too many events
            selectable: true,
            select: function(info) {
                // Pre-fill the create appointment modal with the selected date
                const date = new Date(info.startStr);
                document.getElementById('date').value = info.startStr;

                // Open the create appointment modal
                const createModal = new bootstrap.Modal(document.getElementById('createAppointmentModal'));
                createModal.show();
            }
        });

        calendar.render();

        // Function to display appointment details in the modal
        function showAppointmentDetails(event) {
            const data = event.extendedProps;

            // Set the modal title
            document.getElementById('viewAppointmentModalLabel').textContent = 'Appointment #' + event.id;

            // Set the appointment details
            document.getElementById('appointment-service').textContent = data.service;
            document.getElementById('appointment-date').textContent = event.start.toLocaleDateString();
            document.getElementById('appointment-time').textContent = event.start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) + ' - ' +
                                                                    event.end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            document.getElementById('appointment-client').textContent = data.client;
            document.getElementById('appointment-vendor').textContent = data.vendor;
            document.getElementById('appointment-created').textContent = 'N/A'; // This data might not be available

            // Set notes if available
            if (data.notes) {
                document.getElementById('appointment-notes').textContent = data.notes;
                document.getElementById('appointment-notes-section').style.display = 'block';
            } else {
                document.getElementById('appointment-notes-section').style.display = 'none';
            }

            // Set status with appropriate color
            const statusElement = document.getElementById('appointment-status');
            statusElement.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);

            // Clear any previous classes and add the appropriate one
            statusElement.className = 'badge';
            if (data.status === 'pending') {
                statusElement.classList.add('bg-warning');
            } else if (data.status === 'confirmed') {
                statusElement.classList.add('bg-primary');
            } else if (data.status === 'completed') {
                statusElement.classList.add('bg-success');
            } else if (data.status === 'cancelled') {
                statusElement.classList.add('bg-danger');
            }

            // Update the edit link
            document.getElementById('appointment-edit-link').href = `/admin/appointments/${event.id}/edit`;

            // Open the modal
            const viewModal = new bootstrap.Modal(document.getElementById('viewAppointmentModal'));
            viewModal.show();
        }

        // Filter functionality
        document.getElementById('apply-filters').addEventListener('click', function() {
            const vendorId = document.getElementById('calendar-vendor').value;
            const status = document.getElementById('calendar-status').value;

            calendar.getEvents().forEach(function(event) {
                const data = event.extendedProps;
                let visible = true;

                // Apply vendor filter
                if (vendorId && data.vendor_id.toString() !== vendorId) {
                    visible = false;
                }

                // Apply status filter
                if (status && data.status !== status) {
                    visible = false;
                }

                // Show/hide the event
                if (visible) {
                    event.setProp('display', 'auto');
                } else {
                    event.setProp('display', 'none');
                }
            });
        });

        // Service selection affects end time
        document.getElementById('service_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const duration = selectedOption.getAttribute('data-duration');

            if (duration && document.getElementById('start_time').value) {
                const startTime = document.getElementById('start_time').value;
                const [hours, minutes] = startTime.split(':').map(Number);

                // Calculate end time based on duration (in minutes)
                let endHours = hours;
                let endMinutes = minutes + parseInt(duration);

                // Handle hour overflow
                if (endMinutes >= 60) {
                    endHours += Math.floor(endMinutes / 60);
                    endMinutes = endMinutes % 60;
                }

                // Format for time input (HH:MM)
                const formattedEndHours = endHours.toString().padStart(2, '0');
                const formattedEndMinutes = endMinutes.toString().padStart(2, '0');

                document.getElementById('end_time').value = `${formattedEndHours}:${formattedEndMinutes}`;
            }
        });

        // Start time changes should update end time if service is selected
        document.getElementById('start_time').addEventListener('change', function() {
            const serviceSelect = document.getElementById('service_id');
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const duration = selectedOption.getAttribute('data-duration');

            if (duration) {
                const startTime = this.value;
                const [hours, minutes] = startTime.split(':').map(Number);

                // Calculate end time based on duration (in minutes)
                let endHours = hours;
                let endMinutes = minutes + parseInt(duration);

                // Handle hour overflow
                if (endMinutes >= 60) {
                    endHours += Math.floor(endMinutes / 60);
                    endMinutes = endMinutes % 60;
                }

                // Handle 24-hour format overflow
                if (endHours >= 24) {
                    endHours = endHours % 24;
                }

                // Format for time input (HH:MM)
                const formattedEndHours = endHours.toString().padStart(2, '0');
                const formattedEndMinutes = endMinutes.toString().padStart(2, '0');

                document.getElementById('end_time').value = `${formattedEndHours}:${formattedEndMinutes}`;
            }
        });
    });
</script>
@endsection
