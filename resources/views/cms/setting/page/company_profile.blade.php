<x-form.text 
    id="name" 
    label="Name" 
    :value="$setting->data['name'] ?? null"
    :required="true" 
/>

<x-form.text 
    id="email" 
    label="Email" 
    :value="$setting->data['email'] ?? null"
    :required="true" 
/>

<x-form.text 
    id="address" 
    label="Address" 
    :value="$setting->data['address'] ?? null"
    :required="true" 
/>

<x-form.text 
    id="phone" 
    label="Phone" 
    :value="$setting->data['phone'] ?? null"
    :required="true" 
/>