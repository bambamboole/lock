<?php
declare(strict_types=1);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard.home'));
    $response->assertRedirect(route('login'));
});
