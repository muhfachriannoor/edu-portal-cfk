<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    |  following language lines contain  default error messages used by
    |  validator class. Some of se rules have multiple versions such
    | as  size rules. Feel free to tweak each of se messages here.
    |
    */

    'accepted' => ':attribute harus diterima.',
    'active_url' => ':attribute bukan URL yang valid.',
    'after' => ':attribute harus tanggal setelahnya :date.',
    'after_or_equal' => ':attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'array' => ':attribute harus berupa array.',
    'before' => ':attribute harus tanggal sebelumnya :date.',
    'before_or_equal' => ':attribute harus tanggal sebelum atau sama dengan :date.',
    'between' => [
        'numeric' => ':attribute harus di antara :min dan :max.',
        'file' => ':attribute harus di antara :min dan :max kilobytes.',
        'string' => ':attribute harus di antara :min dan :max characters.',
        'array' => ':attribute harus ada di antara :min dan :max items.',
    ],
    'boolean' => ':attribute input harus true atau false.',
    'confirmed' => ':attribute konfirmasi tidak cocok.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_equals' => ':attribute harus tanggal yang sama dengan :date.',
    'date_format' => ':attribute tidak sesuai dengan format :format.',
    'different' => ':attribute dan :or harus berbeda.',
    'digits' => ':attribute harus :digits digits.',
    'digits_between' => ':attribute harus diantara :min dan :max digits.',
    'dimensions' => ':attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => ':attribute input memiliki nilai duplikat.',
    'email' => ':attribute Harus alamat e-mail yang valid.',
    'ends_with' => ':attribute harus diakhiri dengan salah satu dari berikut ini: :values.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa file.',
    'filled' => ':attribute input harus memiliki nilai.',
    'gt' => [
        'numeric' => ':attribute harus lebih besar dari :value.',
        'file' => ':attribute harus lebih besar dari :value kilobytes.',
        'string' => ':attribute harus lebih besar dari :value karakter.',
        'array' => ':attribute harus memiliki lebih dari :value items.',
    ],
    'gte' => [
        'numeric' => ':attribute harus lebih besar dari atau sama :value.',
        'file' => ':attribute harus lebih besar dari atau sama :value kilobytes.',
        'string' => ':attribute harus lebih besar dari atau sama :value characters.',
        'array' => ':attribute harus punya :value items atau lebih.',
    ],
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => ':attribute bidang tidak ada di :or.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'ip' => ':attribute harus alamat IP yang valid.',
    'ipv4' => ':attribute harus alamat IPv4 yang valid.',
    'ipv6' => ':attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':attribute harus berupa string JSON yang valid.',
    'lt' => [
        'numeric' => ':attribute harus kurang dari :value.',
        'file' => ':attribute harus kurang dari :value kilobytes.',
        'string' => ':attribute harus kurang dari :value karakter.',
        'array' => ':attribute harus memiliki kurang dari :value items.',
    ],
    'lte' => [
        'numeric' => ':attribute harus kurang dari atau sama :value.',
        'file' => ':attribute harus kurang dari atau sama :value kilobytes.',
        'string' => ':attribute harus kurang dari atau sama :value karakter.',
        'array' => ':attribute harus memiliki kurang dari :value items.',
    ],
    'max' => [
        'numeric' => ':attribute mungkin tidak lebih dari :max.',
        'file' => ':attribute mungkin tidak lebih dari :max kilobytes.',
        'string' => ':attribute mungkin tidak lebih dari :max characters.',
        'array' => ':attribute mungkin tidak lebih dari :max items.',
    ],
    'mimes' => ':attribute harus berupa file bertipe: :values.',
    'mimetypes' => ':attribute harus berupa file bertipe: :values.',
    'min' => [
        'numeric' => ':attribute setidaknya harus :min.',
        'file' => ':attribute setidaknya harus :min kilobytes.',
        'string' => ':attribute setidaknya harus :min karakter.',
        'array' => ':attribute setidaknya harus memiliki :min items.',
    ],
    'multiple_of' => ':attribute harus kelipatan :value',
    'not_in' => ':attribute yang di pilih tidak valid.',
    'not_regex' => ':attribute format tidak valid.',
    'numeric' => ':attribute harus berupa angka.',
    'password' => 'password salah.',
    'present' => ':attribute input harus ada.',
    'regex' => ':attribute format tidak valid.',
    'required' => ':attribute input harus di isi.',
    'required_if' => ':attribute input harus diisi saat :or adalah :value.',
    'required_unless' => ':attribute input harus diisi kecuali :or adalah antara :values.',
    'required_with' => ':attribute input harus diisi saat :values ada.',
    'required_with_all' => ':attribute input harus diisi saat :values ada.',
    'required_without' => ':attribute input harus diisi saat :values tidak ada.',
    'required_without_all' => ':attribute bidang harus diisi jika tidak ada :values ada.',
    'same' => ':attribute dan :or harus cocok.',
    'size' => [
        'numeric' => ':attribute harus :size.',
        'file' => ':attribute harus :size kilobytes.',
        'string' => ':attribute harus :size characters.',
        'array' => ':attribute harus mengandung :size items.',
    ],
    'starts_with' => ':attribute harus dimulai dengan salah satu dari berikut ini: :values.',
    'string' => ':attribute harus string.',
    'timezone' => ':attribute harus valid zone.',
    'unique' => ':attribute sudah diambil.',
    'uploaded' => ':attribute gagal mengunggah.',
    'url' => ':attribute format tidak valid.',
    'uuid' => ':attribute harus valid UUID.',
    'hash' => ':attribute tidak valid.',
    'reCaptcha' => ':attribute tidak valid.',
    'coordinate' => ':attribute tidak valid.',
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using 
    | convention "attribute.rule" to name  lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    |  following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
