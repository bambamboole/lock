<?php
declare(strict_types=1);

use Illuminate\Foundation\DevCommands;

it('leaves serving the app to Herd', function (): void {
    $names = array_column(DevCommands::commands(), 'name');

    expect($names)->not->toContain('server');
});
