<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\AdSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Hand-rolled rather than extending the Filament settings plugin's SettingsPage:
 * that plugin pins filament/filament to an exact patch release and would force
 * a panel-wide Filament upgrade. This is the same fill/save contract.
 *
 * @property-read Schema $form
 */
class AdSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRays;

    protected static string|UnitEnum|null $navigationGroup = 'Iklan';

    protected static ?string $title = 'Ad settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(app(AdSettings::class)->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Ad rotation')
                    ->description('Once a visitor has seen the configured number of pages, their next click anywhere on the public site opens one of the active ads in a new tab.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable ads')
                            ->helperText('Switch off to stop serving ads immediately.'),
                        TextInput::make('frequency')
                            ->label('Page views between ads')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(100)
                            ->suffix('page views'),
                        TextInput::make('cooldown_minutes')
                            ->label('Cooldown')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(1440)
                            ->suffix('minutes')
                            ->helperText('Minimum gap between two ads for the same visitor.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $settings = app(AdSettings::class);
        $settings->fill($this->form->getState());
        $settings->save();

        Notification::make()->success()->title('Saved')->send();
    }
}
