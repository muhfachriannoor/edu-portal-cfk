<?php

namespace App\Http\Controllers\Cms;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\Notification;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use App\View\Data\NotificationData;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Cms\CmsController;
use App\Http\Requests\NotificationRequest;
use App\Jobs\SendNotificationJob;

class NotificationController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'notification';
    protected $firebaseService;
    protected $firebaseEnv;

    /**
     * Constructor: Authorize resource wildcard.
     */
    public function __construct()
    {
        $this->firebaseService = new FirebaseService();
        $this->firebaseEnv = config('services.firebase.env');
        $this->authorizeResourceWildcard($this->resourceName);
    }

    /**
     * Display a listing of the resource for Datatables.
     */
    public function datatables()
    {
        return (new Notification)->getDatatables(); 
    }

    /**
     * Display a listing of the resource (Index Page).
     */
    public function index()
    {
        return view("cms.{$this->resourceName}.index", [ // cms.notification.index
            'resourceName' => $this->resourceName,
            'pageMeta' => [
                'title' => 'Notification List'
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Notification $notification)
    {
        return view("cms.{$this->resourceName}.form", [ // cms.notification.form
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'notification' => $notification,
            'pageMeta' => [
                'title' => 'Create Notification',
                'method' => 'post',
                'url' => route('secretgate19.notification.store')
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NotificationRequest $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $notification = Notification::create([
                'title' => $request->title_en,
                'message' => $request->message_en, 
            ]);

            $this->saveTranslations($notification, $request);

            // Job Redis Queue
            SendNotificationJob::dispatch($notification, $request->only([
                'title_en', 'message_en', 'title_id', 'message_id'
            ]));
            
            DB::commit();
            
            return to_route('secretgate19.notification.index')
                ->with('success', 'Notification created successfully.');

        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e; 
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification)
    {
        $notification->load('translations'); 

        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'notification' => $notification,
            'pageMeta' => [
                'title' => 'Edit Notification',
                'method' => null,
                'url' => null
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Notification $notification)
    {
        return view("cms.{$this->resourceName}.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'notification' => $notification,
            'pageMeta' => [
                'title' => 'Edit Notification',
                'method' => 'PUT',
                'url' => route('secretgate19.notification.update', $notification->getKey())
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NotificationRequest $request, Notification $notification)
    {
        DB::beginTransaction();

        try {
            $notification->update([
                'title' => $request->title_en,
                'message' => $request->message_en,
            ]);

            $this->saveTranslations($notification, $request);

            DB::commit();
            
            return to_route('secretgate19.notification.index')
                ->with('success', 'Notification article updated successfully.');
        } catch(\Exception $e){
            DB::rollback();
            report($e);
            throw $e;
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notification): JsonResponse
    {
        $notification->delete();

        return response()->json(null, 204);
    }

    private function saveTranslations($notification, $request)
    {
        $locales = ['en', 'id'];

        foreach ($locales as $locale) {
            $notification->translations()->updateOrCreate(
                ['locale' => $locale],
                [
                    'name' => $request->input("title_{$locale}"),
                    'description' => $request->input("message_{$locale}"),
                ]
            );
        }
    }
}