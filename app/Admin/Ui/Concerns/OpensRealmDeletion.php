<?php

declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Forms\DeleteRealmForm;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Link;
use Lattice\Ui\Components\Modal;

/**
 * Deleting a realm is a form, because the name has to be typed back; the
 * trigger opens it in a modal so it can sit in an action menu.
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
