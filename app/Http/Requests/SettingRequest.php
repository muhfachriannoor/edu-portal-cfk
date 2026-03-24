<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        $settingKey = $this->route('setting');

        switch ($settingKey->key) {
            case 'EMAIL_PREFERENCE':
                $rules = [
                    'brand_news_story_en' => ['required', 'string', 'max:255'],
                    'brand_news_story_id' => ['required', 'string', 'max:255'],

                    'new_product_launch_en' => ['required', 'string', 'max:255'],
                    'new_product_launch_id' => ['required', 'string', 'max:255'],

                    'back_in_stock_alert_en' => ['required', 'string', 'max:255'],
                    'back_in_stock_alert_id' => ['required', 'string', 'max:255'],

                    'order_account_update_en' => ['required', 'string', 'max:255'],                    
                    'order_account_update_id' => ['required', 'string', 'max:255'],

                    'wishlist_price_drop_alert_en' => ['required', 'string', 'max:255'],
                    'wishlist_price_drop_alert_id' => ['required', 'string', 'max:255'],

                    'unsubscribe_en' => ['required', 'string', 'max:255'],                    
                    'unsubscribe_id' => ['required', 'string', 'max:255'],
                ];
                break;
            
            case 'COMPANY_PROFILE':
                $rules = [
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                    'address' => ['required', 'string', 'max:255'],
                    'phone' => ['required', 'string', 'max:15'],
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
                $rules = [
                    'content_en' => ['nullable', 'string', 'max:50000'],
                    'content_id' => ['nullable', 'string', 'max:50000'],
                    'meta_title_en' => ['required', 'string', 'max:60'],
                    'meta_title_id' => ['required', 'string', 'max:60'],
                    'meta_description_en' => ['required', 'string', 'max:160'],
                    'meta_description_id' => ['required', 'string', 'max:160'],
                    'meta_keywords_en' => ['nullable', 'string', 'max:255'],
                    'meta_keywords_id' => ['nullable', 'string', 'max:255'],
                ];
                break;

            case 'SARINAH_API':
                $rules = [ 'url' => ['required'] ];
                break;
            
            case 'ORDER_STATUS_ROLES':
                $rules = [
                    'pending' => ['required', 'array'],
                    'sent_to_courier' => ['required', 'array'],
                    'preparing' => ['required', 'array'],
                    'on_delivery' => ['required', 'array'],
                    'ready_pick_up' => ['required', 'array'],
                    'completed' => ['required', 'array'],
                ];
                break;
        }

        return $rules;
    }

    /**
     * Customize the error messages.
     * 
     * @return array
     */
    public function messages()
    {
        return [
            
        ];
    }
}
