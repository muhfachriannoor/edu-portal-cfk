<?php

namespace App\Services;

use App\Jobs\RecordActivity;
use App\Models\Order;
use App\Models\OrderLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ActivityService
{
    /**'
     * @param $model
     * @param string $action
     * @return void
     */
    protected function recorded($model, string $action): void
    {
        $request = request();
        $user = $request->user('api') ?? $request->user();

        if (!$user && ($action !== 'visit')) {
            return;
        }

        $identifier = $user->id ?? $request->ip();
        $lock = Cache::lock("activity:{$action}:{$model}:{$identifier}", 1);

        if ($lock->get()) {
            $detection = (new BrowserDetection($request->userAgent()))
                ->detect();

            $data = [
                'ip_address' => $request->ip(),
                'browser' => $detection->getBrowser(),
                'browser_version' => $detection->getVersion(),
                'platform' => $detection->getPlatform(),
                'action' => $action,
                'additional_data' => [
                    'headers' => $request->header(),
                    'body' => collect($request->all())->reject(function ($value) {
                        return !is_string($value);
                    })->all()
                ],
                'modelable_id' => $model->getKey(),
                'modelable_type' => get_class($model)
            ];

            RecordActivity::dispatchAfterResponse($user, $data);
            
            $lock->release();
        }
    }

    /**
     * @param string $key
     * @return void
     */
    protected function forgetCacheResource(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * @param $user
     */
    protected function updateLastTokenLogin($user): void
    {
        if (!empty($user->last_token)) {
            $response = $this->pushLogout($user->last_token, $user->id);
            if ($response->successful()) {
                $jwtTTL = (int) config('jwt.ttl');
                Cache::put("logout_user:{$user->id}", $user->last_token, $jwtTTL * 60);
            }
        }

        DB::table('users')->where('id', $user->id)
            ->update([
                'last_token' => $user->new_token,
                'last_logged' => now()->toDateTimeString()
            ]);
    }

    /**
     * @param $token
     * @param $userId
     * @return \Illuminate\Http\Client\Response
     */
    protected function pushLogout($token, $userId)
    {
        $databaseUrl = config('services.google.database_url');
        $path = config('services.google.default_path');
        $endpoint = "multiple_login/{$userId}.json";
        $secret = config('services.google.default_token');

        $url = "{$databaseUrl}/{$path}/{$endpoint}?auth={$secret}";
        $data = [
            'user_id' => $userId,
            'access_token' => $token,
            'timestamp' => now()->valueOf()
        ];

        return Http::asJson()->put($url, $data);
    }

    public function orderLogActivity(Order $order, string $action, ?string $description = null, array $additionalData = []): OrderLog {
        return $order->orderLog()->create([
            'action'          => $action,
            'description'     => $description,
            'additional_data' => $additionalData,
        ]);
    }
    
}
