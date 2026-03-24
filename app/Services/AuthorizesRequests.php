<?php

namespace App\Services;

use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesRequests
{
    /**
     * Authorize a resource action based on the incoming request.
     *
     * @param  string $resource
     * @param  array $options
     * @return void
     */
    public function authorizeResourceWildcard(string $resource, array $options = []): void
    {
        $middleware = [];

        foreach ($this->resourceAbilityMap() as $method => $ability) {
            $middleware["can:{$resource}.{$ability}"][] = $method;
        }

        foreach ($middleware as $middlewareName => $methods) {
            $this->middleware($middlewareName, $options)->only($methods);
        }
    }

    /**
     * @param string $resource
     * @return void
     * @throws AuthorizationException
     */
    public function authorizeResourceCustom(string $resource): void
    {
        $isDoNotHavePermission = auth()
            ->user()
            ->getPermissionsViaRoles()
            ->where('name', $resource)
            ->isEmpty();

        
        if ($isDoNotHavePermission) throw new AuthorizationException;
    }

    /**
     * Get the map of resource methods to ability names.
     *
     * @return array
     */
    protected function resourceAbilityMap(): array
    {
        return [
            'index' => 'viewAny',
            'datatables' => 'viewAny',
            'export' => 'viewAny',
            'show' => 'view',
            'import' => 'create',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];
    }
}