<?php

declare(strict_types=1);

test('health check endpoint returns status ok and database connection', function (): void {
    $response = $this->get('/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'healthy',
            'app' => 'KCAM',
            'database' => 'connected',
        ]);
});
