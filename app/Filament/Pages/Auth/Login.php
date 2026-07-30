<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    public function fillCredentials(): void
    {
        $this->form->fill([
            'email' => 'admin@dispatchlogistics.com',
            'password' => 'Dispatch@2026',
            'remember' => true,
        ]);
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
