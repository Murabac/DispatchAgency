<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function fillCredentials(): void
    {
        $this->form->fill([
            'name' => 'User',
            'password' => 'O4447337@',
            'remember' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'name' => $data['name'],
            'password' => $data['password'],
        ];
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Username')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getFillCredentialsFormAction(),
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getFillCredentialsFormAction(): Action
    {
        return Action::make('fillCredentials')
            ->label('Fill credentials')
            ->color('gray')
            ->action('fillCredentials');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }
}
