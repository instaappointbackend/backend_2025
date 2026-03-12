<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Http\Resources\ContactResource;
use App\Mail\ContactFormSubmission;
use App\Models\AppSetting;
use App\Models\Contact;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the user's contact messages.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $contacts = Contact::where('user_id', auth()->id())->latest()->get();

        return $this->success(ContactResource::collection($contacts), 'Contact messages retrieved successfully.');
    }

    /**
     * Store a newly created contact message in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(ContactRequest $request)
    {
        try {
            $contact = Contact::create([
                'user_id' => auth()->id(),
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => 'unread',
            ]);

            // Get admin email from app_settings
            $adminEmail = AppSetting::where('key', 'admin_email')->value('value');

            // If admin email is not found, fall back to the config value
            if (! $adminEmail) {
                $adminEmail = config('mail.from.address');
            }

            // Send email notification
            Mail::to($adminEmail)->queue(new ContactFormSubmission([
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]));

            return $this->success(new ContactResource($contact), 'Message sent successfully.', 201);
        } catch (\Exception $e) {
            return $this->error('An error occurred while sending your message.', 500);
        }
    }

    /**
     * Display the specified contact message.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $contact = Contact::where('user_id', auth()->id())->findOrFail($id);

        return $this->success(new ContactResource($contact), 'Contact message retrieved successfully.');
    }

    /**
     * Remove the specified contact message from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $contact = Contact::where('user_id', auth()->id())->findOrFail($id);
        $contact->delete();

        return $this->success([], 'Contact message deleted successfully.');
    }
}
