<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

trait BaseModel
{
    /**
     * Convert class name (e.g. SubCategory) → permission key (e.g. sub_category)
     */
    public function getPermissionKey(): string
    {
        $className = class_basename(static::class);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }
}
