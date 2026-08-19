<?php

declare(strict_types=1);

/*
 * Part 2's defining constraint: the admin panel exists alongside the public
 * site without the public site knowing about it. Part 3 rewires the Livewire
 * pages onto Eloquent and needs to start from a provably clean separation.
 */

arch('the public site does not depend on filament')
    ->expect('App\Livewire')
    ->not->toUse('App\Filament');

arch('the support catalogues do not depend on filament')
    ->expect('App\Support')
    ->not->toUse('App\Filament');

arch('admin code does not reach into livewire pages')
    ->expect('App\Filament')
    ->not->toUse('App\Livewire');
