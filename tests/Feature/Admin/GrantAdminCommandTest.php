<?php

declare(strict_types=1);
use App\Admin\Enums\AdminAuditEvent;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Api\ManagementRoles;
use App\Shared\Audit\Models\Activity;
use App\Shared\Authorization\Enums\Scope;

test('admin:grant makes a user an admin and records it with a system actor', function () {
    $user = User::factory()->for(Realm::master())->create();

    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();

    $activity = Activity::global()->sole();

    expect($user->refresh()->can(Scope::RealmsWrite))->toBeTrue()
        ->and($activity->event)->toBe(AdminAuditEvent::SuperAdminGranted->eventName())
        ->and($activity->subject_id)->toBe($user->id)
        ->and($activity->causer_name)->toBe(__('admin.activity.system-actor'))
        ->and($activity->properties?->get('actor'))->toBe('console:admin:grant')
        ->and($activity->properties?->get('added'))->toBe([ManagementRoles::SUPER_ADMIN]);
});

test('re-granting an existing admin is not recorded twice', function () {
    $user = User::factory()->for(Realm::master())->create();

    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();
    $this->artisan('admin:grant', ['email' => $user->email])->assertSuccessful();

    expect(Activity::global()->count())->toBe(1);
});

test('admin:grant fails for an unknown email', function () {
    $this->artisan('admin:grant', ['email' => 'nobody@example.com'])->assertFailed();
});
