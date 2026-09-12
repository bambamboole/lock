<?php

declare(strict_types=1);
use App\Audit\Models\AdminEvent;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Api\ManagementRoles;
use App\Shared\Auth\Enums\UserAdminEvent;
use App\Shared\Authorization\Enums\Scope;

test('admin:grant makes a user an admin and records it with a system actor', function () {
    $user = User::factory()->for(Realm::master())->create();

    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();

    $event = AdminEvent::global()->sole();

    expect($user->refresh()->can(Scope::RealmsWrite))->toBeTrue()
        ->and($event->type)->toBe(UserAdminEvent::SuperAdminGranted->type())
        ->and($event->subject_id)->toBe($user->id)
        ->and($event->actor_name)->toBe(__('admin.events.system-actor'))
        ->and(data_get($event->context, 'actor'))->toBe('console:admin:grant')
        ->and(data_get($event->context, 'added'))->toBe([ManagementRoles::SUPER_ADMIN]);
});

test('re-granting an existing admin is not recorded twice', function () {
    $user = User::factory()->for(Realm::master())->create();

    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();
    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();

    expect(AdminEvent::global()->count())->toBe(1);
});

test('admin:grant fails for an unknown email', function () {
    $this->artisan('admin:grant', ['email' => 'nobody@example.com'])->assertFailed();
});
