<?php

namespace App\Http\Controllers\Cms;

use App\Models\Setting;
use App\Logic\UnictiveLogic;
use Illuminate\Http\Request;
use App\Http\Requests\SettingRequest;
use App\Http\Controllers\Cms\CmsController;
use App\Models\Privilege;

class SettingController extends CmsController
{
    /**
     * @var string
     */
    protected $resourceName = 'setting';

    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->authorizeResourceWildcard($this->resourceName);
    }

    /**
     * Display a listing of the resource.
     */
    public function datatables()
    {
        return (new Setting)->getDatatables();
    }

    /**
     * Get lists.
     */
    public function getLists()
    {
        return [
            'roles' => auth()->user()->getAllRolesForMultiList()
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("cms.setting.index", [
            'resourceName' => $this->resourceName,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Setting List'
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Setting $setting)
    {
        return view("cms.setting.form", [
            'resourceName' => $this->resourceName,
            'mode' => __FUNCTION__,
            'setting' => $setting,
            'lists' => $this->getLists(),
            'pageMeta' => [
                'title' => 'Edit Setting',
                'method' => 'put',
                'url' => route('secretgate19.setting.update', $setting->id)
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SettingRequest $request, Setting $setting)
    {
        $setting->update(
            [
                'data' => $this->handleJsonValue($request->validated(), $setting)
            ]
        );

        return to_route('secretgate19.setting.index', $setting->id)->with('success', 'Setting updated successfully');
    }

    /**
     * @param Request $request
     * @param Setting $setting
     * @return void
     */
    protected function handleJsonValue($validatedData, $setting)
    {
        $request = request();
        $json = [];
        
        switch ($setting->getAttributeValue('key')) {
            case 'EMAIL_PREFERENCE':
                $json = [
                    'en' => [
                        'brand_news_story' => [
                            'title' => 'Brand News & Stories',
                            'text' => $validatedData['brand_news_story_en'] ?? null
                        ],
                        'new_product_launch' => [
                            'title' => 'New Product Launches',
                            'text' => $validatedData['new_product_launch_en'] ?? null
                        ],
                        'back_in_stock_alert' => [
                            'title' => 'Back-in-Stock Alerts',
                            'text' => $validatedData['back_in_stock_alert_en'] ?? null
                        ],
                        'order_account_update' => [
                            'title' => 'Order & Account Updates',
                            'text' => $validatedData['order_account_update_en'] ?? null
                        ],
                        'wishlist_price_drop_alert' => [
                            'title' => 'Wishlist & Price-Drop Alerts',
                            'text' => $validatedData['wishlist_price_drop_alert_en'] ?? null
                        ],
                        'unsubscribe' => [
                            'title' => 'Unsubscribe from all email list',
                            'text' => $validatedData['unsubscribe_en'] ?? null
                        ],
                    ],
                    'id' => [
                        'brand_news_story' => [
                            'title' => 'Berita & Cerita Brand',
                            'text' => $validatedData['brand_news_story_id'] ?? null
                        ],
                        'new_product_launch' => [
                            'title' => 'Peluncuran Produk Baru',
                            'text' => $validatedData['new_product_launch_id'] ?? null
                        ],
                        'back_in_stock_alert' => [
                            'title' => 'Peringatan Stok Kembali',
                            'text' => $validatedData['back_in_stock_alert_id'] ?? null
                        ],
                        'order_account_update' => [
                            'title' => 'Pembaruan Pesanan & Akun',
                            'text' => $validatedData['order_account_update_id'] ?? null
                        ],
                        'wishlist_price_drop_alert' => [
                            'title' => 'Daftar Keinginan & Peringatan Penurunan Harga',
                            'text' => $validatedData['wishlist_price_drop_alert_id'] ?? null
                        ],
                        'unsubscribe' => [
                            'title' => 'Berhenti berlangganan dari semua daftar email',
                            'text' => $validatedData['unsubscribe_id'] ?? null
                        ],
                    ]
                ];
            break;

            case 'HOME':
            case 'TERM_AND_CONDITION':
            case 'PRIVACY_POLICY':
            case 'HOW_TO_ORDER':
            case 'ABOUT_SARINAH':
            case 'INTELLECTUAL_PROPERTY':
            case 'NEWSROOM':
            case 'CAREERS':
            case 'AFFILIATES':
            case 'SARINAH_CARE':
            case 'SELL_ON_SARINAH':
            case 'SHIPPING':
            case 'RETURN_POLICY':
            case 'EVENTS':
            case 'SUSTAINABILITY':
                $json = [
                    'en' => [
                        'meta_title' => $this->sanitizeMeta($validatedData['meta_title_en'] ?? null),
                        'meta_description' => $this->sanitizeMeta($validatedData['meta_description_en'] ?? null),
                        'meta_keywords' => $this->sanitizeMeta($validatedData['meta_keywords_en'] ?? null),
                        'content' => $validatedData['content_en'] ?? null,
                    ],
                    'id' => [
                        'meta_title' => $this->sanitizeMeta($validatedData['meta_title_id'] ?? null),
                        'meta_description' => $this->sanitizeMeta($validatedData['meta_description_id'] ?? null),
                        'meta_keywords' => $this->sanitizeMeta($validatedData['meta_keywords_id'] ?? null),
                        'content' => $validatedData['content_id'] ?? null,
                    ]
                ];
            break;

            case 'SARINAH_API':
                $json = [
                    'url' => $validatedData['url']
                ];
            break;

            case 'ORDER_STATUS_ROLES':
                $json = [
                    'pending' => $this->getRolesData($validatedData['pending']),
                    'sent_to_courier' => $this->getRolesData($validatedData['sent_to_courier']),
                    'preparing' => $this->getRolesData($validatedData['preparing']),
                    'on_delivery' => $this->getRolesData($validatedData['on_delivery']),
                    'ready_pick_up' => $this->getRolesData($validatedData['ready_pick_up']),
                    'completed' => $this->getRolesData($validatedData['completed']),
                ];
            break;
        }

        return $json;
    }

    protected function handleImage(Request $request, $setting = null): void
    {
        if ($request->hasFile('footer_section_image_url')) {
            if ($setting) {
                $setting->deleteImage();  // Hapus gambar lama jika ada
            }
            $path = $request->file('footer_section_image_url')->store('setting', 'public');
            $request->merge(['footer_section_image_url' => $path]);
        }
    }

    protected function getRolesData($roles)
    {
        return Privilege::whereIn('id', $roles)
            ->get(['id', 'name'])
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name
                ];
            })->toArray();
    }

    /**
     * Helper function to sanitize meta fields (strip HTML tags)
     */
    protected function sanitizeMeta($data)
    {
        return $data ? strip_tags($data) : $data;
    }
}