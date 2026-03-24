<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\View\Data\BannerData;

class SettingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/setting",
     *     operationId="getSetting",
     *     tags={"Setting"},
     *     summary="Retrieve setting content by key and locale.",
     *     description="You can pass the `key` query parameter (e.g., TERM_AND_CONDITION, PRIVACY_POLICY, HOW_TO_ORDER, ABOUT_SARINAH, INTELLECTUAL_PROPERTY, NEWSROOM, CAREERS, AFFILIATES, SARINAH_CARE, SELL_ON_SARINAH, SHIPPING, RETURN_POLICY, EVENTS, SUSTAINABILITY, HOME, BANNER_PROMOTION). The content is localized based on the `X-Localization` header or application locale.",
     *
     *     @OA\Parameter(
     *         name="X-Localization",
     *         in="header",
     *         description="The desired language for localized data ('id' or 'en').",
     *         required=false,
     *         @OA\Schema(type="string", default="en", enum={"id", "en"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="key",
     *         in="query",
     *         required=true,
     *         description="You can pass the `key` query parameter (e.g., TERM_AND_CONDITION, PRIVACY_POLICY, HOW_TO_ORDER, ABOUT_SARINAH, INTELLECTUAL_PROPERTY, NEWSROOM, CAREERS, AFFILIATES, SARINAH_CARE, SELL_ON_SARINAH, SHIPPING, RETURN_POLICY, EVENTS, SUSTAINABILITY, HOME, BANNER_PROMOTION). The content is localized based on the `X-Localization` header or application locale.",
     *         @OA\Schema(type="string", example="TERM_AND_CONDITION")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Content retrieved successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Content retrieved successfully."),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="key", type="string", example="TERM_AND_CONDITION"),
     *                 @OA\Property(
     *                     property="text",
     *                     type="string",
     *                     nullable=true,
     *                     example="Terms and conditions content here..."
     *                 ),
     *                 @OA\Property(
     *                     property="seo",
     *                     type="object",
     *                     @OA\Property(property="title", type="string", example="Terms and Conditions - Sarinah"),
     *                     @OA\Property(property="description", type="string", example="Read the full terms and conditions of Sarinah..."),
     *                     @OA\Property(property="keywords", type="string", example="terms, conditions, service")
     *                 ),
     *                 @OA\Property(
     *                     property="banners",
     *                     type="array",
     *                     nullable=true,
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="string", example="9b2f2d8d-8d2a-4ef2-a4f2-1d6d1d9c1a11"),
     *                         @OA\Property(property="name", type="string", example="Home Hero Banner"),
     *                         @OA\Property(property="sequence", type="integer", example=1),
     *                         @OA\Property(property="is_active", type="boolean", example=true),
     *                         @OA\Property(property="image", type="string", nullable=true, example="https://cdn.example.com/uploads/banner.jpg"),
     *                         @OA\Property(property="headline", type="string", example="Discover handmade treasures..."),
     *                         @OA\Property(property="subheadline", type="string", example="Find one-of-a-kind pieces...")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Content not found for the requested key or locale.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Content not found."),
     *             @OA\Property(property="data", type="array", @OA\Items(), example={})
     *         )
     *     ),
     *
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function setting()
    {
        $key = request('key');
        $locale = app()->getLocale();

        // --- Special keys for SEO ---
        $seoKeys = [
            'TERM_AND_CONDITION', 'PRIVACY_POLICY', 'HOW_TO_ORDER', 'ABOUT_SARINAH', 
            'INTELLECTUAL_PROPERTY', 'NEWSROOM', 'CAREERS', 'AFFILIATES', 'SARINAH_CARE', 
            'SELL_ON_SARINAH', 'SHIPPING', 'RETURN_POLICY', 'EVENTS', 'SUSTAINABILITY'
        ];

        // --- Default: Setting content ---
        $setting = Setting::query()
            ->when($key, function ($query) use ($key) {
                $query->where('key', $key);
            })
            ->first();
        $data = $setting->data ?? [];
        

        // FOR CONTENT WITH SEO
        $textContent = $data[$locale]['content'] ?? null;

        // --- Special key: HOME ---
        if (strtoupper((string) $key) === 'HOME') {
            $banners = BannerData::listsForApiByCategory('HOME_NOT_LOGGED', $locale);

            if (!empty($banners)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Home retrieved successfully.',
                    'data' => [
                        'key' => 'HOME',
                        'banners' => $banners,
                        'seo' => [
                            'title' => $data[$locale]['meta_title'] ?? '',
                            'description' => $data[$locale]['meta_description'] ?? '',
                            'keywords' => $data[$locale]['meta_keywords'] ?? '',
                        ]
                    ],
                ], Response::HTTP_OK);
            }

            return response()->json([
                'success' => true,
                'message' => 'Content not found.',
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        // --- Special key: BANNER_PROMOTION ---
        if (strtoupper((string) $key) === 'BANNER_PROMOTION') {
            $banners = BannerData::listsForApiByCategory('PROMOTION', $locale, 3);

            if (!empty($banners)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Banner Promotion retrieved successfully.',
                    'data' => [
                        'key' => 'BANNER_PROMOTION',
                        'banners' => $banners,
                    ],
                ], Response::HTTP_OK);
            }

            return response()->json([
                'success' => true,
                'message' => 'Content not found.',
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }

        // Add SEO Data for Special Keys
        if (in_array($key, $seoKeys)) {
            $seo = [
                'title' => $data[$locale]['meta_title'] ?? '',
                'description' => $data[$locale]['meta_description'] ?? '',
                'keywords' => $data[$locale]['meta_keywords'] ?? '',
            ];

            if ($textContent) {
                return response()->json([
                    'success' => true,
                    'message' => 'Content retrieved successfully.',
                    'data' => [
                        'key' => $key,
                        'text' => $textContent,
                        'seo' => $seo,
                    ],
                ], Response::HTTP_OK);
            }
        }

        // Safely get the value for the locale (avoid error when $setting is null)
        $text = $data[$locale] ?? null;

        if ($text) {
            return response()->json([
                'success' => true,
                'message' => 'Content retrieved successfully.',
                'data' => [
                    'key' => $key,
                    'text' => $text,
                ],
            ], Response::HTTP_OK);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content not found.',
            'data' => [],
        ], Response::HTTP_NOT_FOUND);
    }
}