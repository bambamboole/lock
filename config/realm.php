<?php
declare(strict_types=1);

return [
    /*
     * The realm whose users administer the instance. Its members carry the
     * global permissions; every other realm is data to them. The realm is
     * created by a data migration and can neither be deleted nor renamed.
     */
    'master_slug' => env('REALM_MASTER_SLUG', 'admin'),
];
