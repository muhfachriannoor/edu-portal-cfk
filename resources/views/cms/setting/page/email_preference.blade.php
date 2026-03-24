<div x-show="tab === 'en'" x-cloak class="space-y-5">
    <x-form.text
        id="brand_news_story_en"
        :label="$setting->data['en']['brand_news_story']['title']  ?? '' "
        :value="$setting->data['en']['brand_news_story']['text'] ?? ''"
    />
    
    <x-form.text
        id="new_product_launch_en"
        :label="$setting->data['en']['new_product_launch']['title']  ?? '' "
        :value="$setting->data['en']['new_product_launch']['text'] ?? ''"
    />
    
    <x-form.text
        id="back_in_stock_alert_en"
        :label="$setting->data['en']['back_in_stock_alert']['title']  ?? '' "
        :value="$setting->data['en']['back_in_stock_alert']['text'] ?? ''"
    />
    
    <x-form.text
        id="order_account_update_en"
        :label="$setting->data['en']['order_account_update']['title']  ?? '' "
        :value="$setting->data['en']['order_account_update']['text'] ?? ''"
    />
    
    <x-form.text
        id="wishlist_price_drop_alert_en"
        :label="$setting->data['en']['wishlist_price_drop_alert']['title']  ?? '' "
        :value="$setting->data['en']['wishlist_price_drop_alert']['text'] ?? ''"
    />
    
    <x-form.text
        id="unsubscribe_en"
        :label="$setting->data['en']['unsubscribe']['title']  ?? '' "
        :value="$setting->data['en']['unsubscribe']['text'] ?? ''"
    />
</div>

<div x-show="tab === 'id'" x-cloak class="space-y-5">    
    <x-form.text
        id="brand_news_story_id"
        :label="$setting->data['id']['brand_news_story']['title']  ?? '' "
        :value="$setting->data['id']['brand_news_story']['text'] ?? ''"
    />
    
    <x-form.text
        id="new_product_launch_id"
        :label="$setting->data['id']['new_product_launch']['title']  ?? '' "
        :value="$setting->data['id']['new_product_launch']['text'] ?? ''"
    />
    
    <x-form.text
        id="back_in_stock_alert_id"
        :label="$setting->data['id']['back_in_stock_alert']['title']  ?? '' "
        :value="$setting->data['id']['back_in_stock_alert']['text'] ?? ''"
    />
    
    <x-form.text
        id="order_account_update_id"
        :label="$setting->data['id']['order_account_update']['title']  ?? '' "
        :value="$setting->data['id']['order_account_update']['text'] ?? ''"
    />
    
    <x-form.text
        id="wishlist_price_drop_alert_id"
        :label="$setting->data['id']['wishlist_price_drop_alert']['title']  ?? '' "
        :value="$setting->data['id']['wishlist_price_drop_alert']['text'] ?? ''"
    />
    
    <x-form.text
        id="unsubscribe_id"
        :label="$setting->data['id']['unsubscribe']['title']  ?? '' "
        :value="$setting->data['id']['unsubscribe']['text'] ?? ''"
    />
</div>