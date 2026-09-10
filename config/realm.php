<?php
declare(strict_types=1);

return [
    /*
     * The realm whose users administer the instance. Its members carry the
     * global permissions; every other realm is data to them.
     */
    'master_slug' => env('REALM_MASTER_SLUG', 'admin'),

    /*
     * Where self-service registration lands while the realm is not yet derived
     * from the request path. Once the path names the realm this stays as the
     * fallback for requests that do not.
     */
    'default_slug' => env('REALM_DEFAULT_SLUG', 'default'),
];
