<?php

namespace SmartCms\Kit\Observers;

use Illuminate\Support\Facades\Log;
use SmartCms\Forms\Models\ContactForm;
use SmartCms\Kit\Models\Admin;
use SmartCms\Kit\Notifications\NewContactFormNotification;

class ContactFormObserver
{
    public function saved(ContactForm $contactForm)
    {
        foreach (Admin::all() as $admin) {
            try {
                $admin->notify(new NewContactFormNotification($contactForm));
            } catch (\Exception $e) {
                Log::error('Error sending contact form notification to admin:', [
                    'message' => $e->getMessage(),
                    'admin' => $admin->id,
                    'contact_form' => $contactForm->id,
                    'trace' => 'ContactFormObserver.saved',
                ]);
            }
        }
    }
}
