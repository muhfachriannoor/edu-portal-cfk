<?php

namespace App\Http\View\Composers;

use App\Models\Store;
use Illuminate\View\View;

class AdminComposer
{
    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view): void
    {
        $view->with($this->bindToView());
    }

    /**
     * @return array
     */
    protected function bindToView(): array
    {
        $user = auth()->user();
        
        $resources = ($user) ? $this->getResources($user) : [];

        $storeCms = request()->route('store');
        return compact('resources', 'storeCms');
    }

    /**
     * @param $user
     * @return array
     */
    protected function getResources($user): array
    {
        return $user->getAllPermissions()
            ->pluck('name')
            ->reject(function ($name) {
                list(,$action) = explode('.', $name);
                return $action !== 'viewAny';
            })
            ->map(function ($name) {
                list($resource) = explode('.', $name);
                return $resource;
            })
            ->values()
            ->toArray();
    }
}