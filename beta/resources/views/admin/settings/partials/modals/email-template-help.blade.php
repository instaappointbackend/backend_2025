<!-- resources/views/admin/settings/partials/modals/email-template-help.blade.php -->
<div class="modal fade" id="emailTemplateHelp" tabindex="-1" aria-labelledby="emailTemplateHelpLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailTemplateHelpLabel">Email Template Variables</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>You can use the following variables in your email templates. They will be replaced with actual values when the email is sent.</p>
                
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Variable</th>
                                <th>Description</th>
                                <th>Example</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>{name}</code></td>
                                <td>Customer's full name</td>
                                <td>John Smith</td>
                            </tr>
                            <tr>
                                <td><code>{first_name}</code></td>
                                <td>Customer's first name</td>
                                <td>John</td>
                            </tr>
                            <tr>
                                <td><code>{service}</code></td>
                                <td>Service name</td>
                                <td>Hair Cut</td>
                            </tr>
                            <tr>
                                <td><code>{provider}</code></td>
                                <td>Service provider's name</td>
                                <td>Jane Doe</td>
                            </tr>
                            <tr>
                                <td><code>{date}</code></td>
                                <td>Appointment date</td>
                                <td>April 15, 2025</td>
                            </tr>
                            <tr>
                                <td><code>{time}</code></td>
                                <td>Appointment time</td>
                                <td>10:30 AM</td>
                            </tr>
                            <tr>
                                <td><code>{duration}</code></td>
                                <td>Appointment duration</td>
                                <td>60 minutes</td>
                            </tr>
                            <tr>
                                <td><code>{location}</code></td>
                                <td>Business location</td>
                                <td>123 Main Street, City</td>
                            </tr>
                            <tr>
                                <td><code>{booking_id}</code></td>
                                <td>Unique booking ID</td>
                                <td>BK123456</td>
                            </tr>
                            <tr>
                                <td><code>{amount}</code></td>
                                <td>Payment amount</td>
                                <td>₹1500.00</td>
                            </tr>
                            <tr>
                                <td><code>{company_name}</code></td>
                                <td>Your business name</td>
                                <td>InstaAppoint</td>
                            </tr>
                            <tr>
                                <td><code>{cancel_hours}</code></td>
                                <td>Hours needed for cancellation</td>
                                <td>24</td>
                            </tr>
                            <tr>
                                <td><code>{otp}</code></td>
                                <td>One-time password</td>
                                <td>123456</td>
                            </tr>
                            <tr>
                                <td><code>{payment_method}</code></td>
                                <td>Payment method used</td>
                                <td>Credit Card</td>
                            </tr>
                            <tr>
                                <td><code>{transaction_id}</code></td>
                                <td>Payment transaction ID</td>
                                <td>TXN123456789</td>
                            </tr>
                            <tr>
                                <td><code>{payment_date}</code></td>
                                <td>Date of payment</td>
                                <td>April 14, 2025</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>