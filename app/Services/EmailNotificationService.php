<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Setting;
use App\Notifications\GeneralEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    public function send(string $statusKey, $model, array $payload, ?string $userEmail = null)
    {
        try {
            // 1. Fetch role mapping from settings
            $setting = Setting::where('key', 'ORDER_STATUS_ROLES')->first();
            $rolesMapping = $setting->data ?? [];
            
            // 2. Identify target roles based on the status key
            $targetRoles = $rolesMapping[$statusKey] ?? [];
            $roleNames = collect($targetRoles)->pluck('name')->toArray();

            if (!in_array('Superadmin', $roleNames)) {
                $roleNames[] = 'Superadmin';
            }

            // 3. Get Admins with those roles + Superadmin (always included)
            $admins = Admin::role($roleNames)
                ->where('is_active', true)
                ->get();

            Log::info("Targeting Admins count: " . $admins->count());
            foreach($admins as $admin) {
                Log::info("Sending admin email to: " . $admin->email);
            }

            // 4. Dispatch to Admins
            if ($admins->isNotEmpty() && isset($payload['admin'])) {
                foreach ($admins as $admin) {
                    $adminPayload = $payload['admin'];
                    $adminPayload['role_name'] = $admin->getRoleNames()->first() ?? 'Admin';
                    
                    $admin->notify((new GeneralEmailNotification($model, $adminPayload))
                        ->onQueue('high'));
                }
                Log::info("Individual emails queued for each Admin.");
            }

            // 5. Dispatch to Frontend User
            if ($userEmail && isset($payload['user'])) {
                Notification::route('mail', $userEmail)
                    ->notify((new GeneralEmailNotification($model, $payload['user']))
                    ->onQueue('high'));
                Log::info("Email [{$statusKey}] successfully queued for User: {$userEmail}");
            }

        } catch (\Exception $e) {
            Log::error("EmailNotificationService failed: " . $e->getMessage());
        }
    }
}