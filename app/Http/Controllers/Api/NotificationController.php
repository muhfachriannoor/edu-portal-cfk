<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use App\Models\UserNotification;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use App\View\Data\NotificationData;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationController extends Controller
{
    protected $firebaseService;
    protected $firebaseEnv;

    public function __construct()
    {
        $this->firebaseService = new FirebaseService();
        $this->firebaseEnv = config('services.firebase.env');
    }

    /**
     * @OA\Get(
     *     path="/notification/list",
     *     summary="Get list of notifications for authenticated user",
     *     description="Returns a paginated list of user notifications stored in the database.",
     *     tags={"Notification"},
     *     security={{"bearerAuth": {}}},
     * 
     *     @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),
     *
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter notifications by type (e.g., general, order)",
     *         required=false,
     *         example="general",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         example=1,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         example=10,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Notification list successfully retrieved.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="List notification has been successfully retrieved."),
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="uuid", type="string", example="a0830328-cc71-44d9-bb71-cb088404f26f"),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="user_email", type="string", example="john@example.com"),
     *                     @OA\Property(property="user_name", type="string", example="John Doe"),
     *                     @OA\Property(property="type", type="string", example="general"),
     *                     @OA\Property(property="title", type="string", example="Your order has been shipped"),
     *                     @OA\Property(property="message", type="string", example="<p>Your order #1234 is on the way.</p>"),
     *                     @OA\Property(property="target", type="string", example="order_detail"),
     *                     @OA\Property(property="is_read", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="integer", example=1764845760929)
     *                 )
     *             ),
     *
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=120),
     *                 @OA\Property(property="last_page", type="integer", example=12)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */

    public function list(): JsonResponse
    {
        $type = request()->get('type', 'all');
        $perPage = (int) request()->get('per_page', 10);
        $user = auth()->user();

        $data = NotificationData::listsForApi($user, $type, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'List notification has been successfully retrieved.',
            'data'    => $data['items'],
            'meta'    => $data['meta'],
        ], Response::HTTP_OK);
    }

    
    /**
     * @OA\Get(
     *      path="/notification/mark-read/all",
     *      operationId="notificationReadAll",
     *      tags={"Notification"},
     *      summary="Mark notifications as read for the authenticated user.",
     *      description="Marks notifications as read based on the provided type. 
     *          - If type = all → Marks all types ['order', 'general'] as read.
     *          - If type = order/general → Marks only that type as read.
     *          - If type is omitted → Marks all user notifications as read.
     *      ",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),
     * 
     *      @OA\Parameter(
     *          name="type",
     *          in="query",
     *          description="Filter notification type to mark as read. 
     *              - all = mark both 'order' and 'general'
     *              - order = only mark order notifications
     *              - general = only mark general notifications
     *              - null/empty = mark all user notifications",
     *          required=false,
     *          @OA\Schema(
     *              type="string",
     *              enum={"all","order","general"},
     *              example="all"
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Notifications marked as read successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="All notifications have been marked as read.")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized - user not authenticated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function all(): JsonResponse
    {
        $type = request()->get('type');
        $user = auth()->user();

        $query = UserNotification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->when($type === 'all', function ($q) {
                return $q->whereIn('type', ['order', 'general']);
            })
            ->when(in_array($type, ['order', 'general']), function ($q) use ($type) {
                return $q->where('type', $type);
            });

        if ($query->count() > 0) {
            $query->update(['read_at' => now()]);
            NotificationData::flush($user->id);
            $this->firebaseService->unreadCount($user, $this->firebaseEnv);
        }

         return response()->json([
            'success' => true,
            'message' => 'All notification has marked as read.'
        ], Response::HTTP_OK);
    }

    /**
     * @OA\Get(
     *      path="/notification/read/{uuid}",
     *      operationId="notificationReadNotification",
     *      tags={"Notification"},
     *      summary="Get a single notification and mark it as read.",
     *      description="Retrieve a selected notification by UUID, mark it as read, and update the unread count.",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\Parameter(
     *          name="uuid",
     *          in="path",
     *          required=true,
     *          description="UUID of the notification",
     *          @OA\Schema(type="string", example="a07ee2a3-f4f9-462f-bdde-2078118151fa")
     *      ),
     * 
     *      @OA\Parameter(
     *          name="X-Localization",
     *          in="header",
     *          description="The desired language for localized data ('id' or 'en').",
     *          required=false,
     *          @OA\Schema(type="string", default="en", enum={"id", "en"})
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Notification retrieved successfully.",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Notification data has been retrieved."),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(property="uuid", type="string", example="a07ee2a3-f4f9-462f-bdde-2078118151fa"),
     *                  @OA\Property(property="title", type="string", example="Title 10"),
     *                  @OA\Property(property="message", type="string", example="<p>This is message 10</p>"),
     *                  @OA\Property(property="is_read", type="boolean", example=true),
     *                  @OA\Property(property="user_id", type="integer", example=1),
     *                  @OA\Property(property="user_name", type="string", example="Endy"),
     *                  @OA\Property(property="user_email", type="string", example="endy@unictive.id")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=404,
     *          description="Notification not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Notification not found."),
     *              @OA\Property(property="data", type="array", @OA\Items())
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized - user not authenticated",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function read($uuid)
    {
        $user = auth()->user();
        $locale = app()->getLocale();

        $notif = UserNotification::with('notification.translations')
            ->where('user_id', $user->id)
            ->find($uuid);
        
        if ($notif) {
            if (is_null($notif->read_at)) {
                $notif->update(['read_at' => now()]);
                $this->firebaseService->unreadCount($user, $this->firebaseEnv);
            }

            $translation = $notif->notification?->translations->firstWhere('locale', $locale);

            $data = [
                'uuid'       => $notif->id,
                'user_id'    => $notif->user?->id,
                'user_email' => $notif->user?->email,
                'user_name'  => $notif->user?->name,
                'type'       => $notif->type,
                'title'      => $translation->name ?? $notif->title,
                'message'    => $translation->description ?? $notif->message,
                'target'     => $notif->target,
                'is_read'    => true,
                'created_at' => $notif->created_timestamp
            ];

            return response()->json([
                'success' => true,
                'message' => 'Notification data has been retrieved.',
                'data'    => $data
            ], Response::HTTP_OK);
        }

        return response()->json([
            'success' => false,
            'message' => 'Notification not found.',
            'data' => [] 
        ], Response::HTTP_NOT_FOUND);
    }
}