<?php

declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Forms\DeleteRealmForm;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Link;
use Lattice\Ui\Components\Modal;

/**
 * Deleting a realm is a form, not an action: it only proceeds once the realm's
 * name has been typed back. The trigger carries that form in a modal so it can
 * sit in an action menu next to plain actions.
 */
trait OpensRealmDeletion
{
    private function deleteRealmTrigger(string $slug): Link
    {
        return Link::make(__('admin.realms.danger.submit'), 'delete-realm-'.$slug)
            ->unstyled()
            ->can(AdminPermission::ManageRealms)
            ->visible($slug !== config('realm.master_slug'))
            ->modal(
                Modal::make('admin.realms.delete.'.$slug)
                    ->title(__('admin.realms.danger.heading'))
                    ->description(__('admin.realms.danger.subtitle'))
                    ->schema([Form::use(DeleteRealmForm::class, ['realm' => $slug])]),
            );
    }
}
