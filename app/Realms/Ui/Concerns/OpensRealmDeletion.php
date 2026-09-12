<?php

declare(strict_types=1);

namespace App\Realms\Ui\Concerns;

use App\Realms\Ui\Forms\DeleteRealmForm;
use App\Shared\Authorization\Enums\Scope;
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
            ->can(Scope::RealmsWrite)
            ->visible($slug !== config('lock.master_realm'))
            ->modal(
                Modal::make('admin.realms.delete.'.$slug)
                    ->title(__('admin.realms.danger.heading'))
                    ->description(__('admin.realms.danger.subtitle'))
                    ->schema([Form::use(DeleteRealmForm::class, ['realm' => $slug])]),
            );
    }
}
