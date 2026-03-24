<?php

namespace App\Traits;

use App\Models\SeoTranslation;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeo
{
    /**
     * Get all SEO translations associated with the model.
     * Useful for admin panels where you need to manage multiple languages at once.
     * 
     * @return MorphMany
     */
    public function seos(): MorphMany
    {
        return $this->morphMany(SeoTranslation::class, 'seoable');
    }

    /**
     * Get the SEO translation for the current application locale.
     * This method automatically relies on your SetLocale Middleware via app()->getLocale().
     * 
     * @return MorphOne
     */
    public function currentSeo(): MorphOne
    {
        // Automatically uses the locale set by your SetLocale Middleware
        return $this->morphOne(SeoTranslation::class, 'seoable')
            ->where('locale', app()->getLocale());
    }

    /**
     * Helper to get SEO by a specific locale manually if needed.
     * 
     * @param string $locale
     * @return MorphOne
     */
    public function seoByLocale(string $locale): MorphOne
    {
        return $this->morphOne(SeoTranslation::class, 'seoable')
            ->where('locale', $locale);
    }

    /**
     * Clean SEO input data by stripping HTML tags to ensure plain text.
     * Recommended to call this before saving data to the database.
     * 
     * @param array $data
     * @return array
     */
    public function sanitizeSeoData(array $data): array
    {
        return array_map(function ($value) {
            return is_string($value) ? strip_tags($value) : $value;
        }, $data);
    }
}