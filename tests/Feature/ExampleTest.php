<?php

declare(strict_types=1);

test('the application returns a successful response', function (): void {
    // The home page renders the catalogue, so it needs one.
    seedCatalog();

    $this->get('/')->assertStatus(200);
});
