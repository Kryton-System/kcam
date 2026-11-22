<?php

declare(strict_types=1);

test('guests visiting the home page are sent to login', function (): void {
    $this->get('/')->assertRedirect('/login');
});
