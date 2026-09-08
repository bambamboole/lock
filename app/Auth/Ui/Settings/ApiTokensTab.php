<?php

declare(strict_types=1);

namespace App\Auth\Ui\Settings;

use App\Auth\Ui\Forms\CreateApiTokenForm;
use App\Auth\Ui\Tables\ApiTokensTable;
use Lattice\Form\Components\Form;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class ApiTokensTab
{
    public function __invoke(): Tab
    {
        return Tab::make('api-tokens', __('tenant.settings.tabs.api-tokens'))
            ->schema([
                Card::make(__('tenant.settings.api-tokens.heading'), __('tenant.settings.api-tokens.subtitle'))
                    ->schema([
                        Form::use(CreateApiTokenForm::class),
                        Table::lazy(ApiTokensTable::class),
                    ]),
            ]);
    }
}
