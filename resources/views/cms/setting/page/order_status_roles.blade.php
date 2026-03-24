<div class="space-y-5">
    <x-form.multi-listbox
        id="pending"
        label="Status Pending"
        :options="$lists['roles']"
        :selected="isset($setting->data['pending']) ? array_column($setting->data['pending'], 'id') : []"
    />

    <x-form.multi-listbox
        id="sent_to_courier"
        label="Status Placed (Ship by Courier)"
        :options="$lists['roles']"
        :selected="isset($setting->data['sent_to_courier']) ? array_column($setting->data['sent_to_courier'], 'id') : []"
    />

    <x-form.multi-listbox
        id="preparing"
        label="Status Preparing"
        :options="$lists['roles']"
        :selected="isset($setting->data['preparing']) ? array_column($setting->data['preparing'], 'id') : []"
    />

    <x-form.multi-listbox
        id="on_delivery"
        label="Status On Delivery (Ship by Courier)"
        :options="$lists['roles']"
        :selected="isset($setting->data['on_delivery']) ? array_column($setting->data['on_delivery'], 'id') : []"
    />

    <x-form.multi-listbox
        id="ready_pick_up"
        label="Status Ready for Pick Up (Pick Up in Store)"
        :options="$lists['roles']"
        :selected="isset($setting->data['ready_pick_up']) ? array_column($setting->data['ready_pick_up'], 'id') : []"
    />

    <x-form.multi-listbox
        id="completed"
        label="Status Completed"
        :options="$lists['roles']"
        :selected="isset($setting->data['completed']) ? array_column($setting->data['completed'], 'id') : []"
    />
</div>