<?php
declare(strict_types=1);

return [
    /*
     * The realm whose users administer the instance. Its members carry the
     * global permissions; every other realm is data to them.
     */
    'master_slug' => env('REALM_MASTER_SLUG', 'master'),

    /*
     * Where self-service registration lands while the realm is not yet derived
     * from the request host. Once hosts resolve realms this becomes a fallback
     * for requests that name no realm.
     */
    'default_slug' => env('REALM_DEFAULT_SLUG', 'default'),
];
