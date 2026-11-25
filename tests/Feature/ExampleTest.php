<?php

declare(strict_types=1);

test('guests visiting the home page see the business landing page', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('KCAM')
        ->assertSee(route('login'));
});
