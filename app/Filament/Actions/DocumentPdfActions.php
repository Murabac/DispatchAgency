<?php

namespace App\Filament\Actions;

use App\Mail\DocumentPdfMail;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\ActionGroup as TableActionGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class DocumentPdfActions
{
    public static function headerGroup(string $type): ActionGroup
    {
        return ActionGroup::make([
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (Model $record): string => route("pdf.{$type}.download", $record))
                ->openUrlInNewTab(),
            Action::make('printPdf')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->url(fn (Model $record): string => route("pdf.{$type}.print", $record))
                ->openUrlInNewTab(),
            self::emailHeaderAction($type),
        ])
            ->label('PDF')
            ->icon('heroicon-o-document-arrow-down')
            ->button()
            ->color('gray');
    }

    public static function tableGroup(string $type): TableActionGroup
    {
        return TableActionGroup::make([
            TableAction::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (Model $record): string => route("pdf.{$type}.download", $record))
                ->openUrlInNewTab(),
            TableAction::make('printPdf')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->url(fn (Model $record): string => route("pdf.{$type}.print", $record))
                ->openUrlInNewTab(),
            self::emailTableAction($type),
        ]);
    }

    protected static function emailHeaderAction(string $type): Action
    {
        return Action::make('emailPdf')
            ->label('Email PDF')
            ->icon('heroicon-o-envelope')
            ->modalHeading('Email PDF to client')
            ->modalSubmitActionLabel('Send email')
            ->form(fn (Model $record): array => self::emailFormSchema($record, $type))
            ->action(fn (Model $record, array $data) => self::sendEmail($record, $data));
    }

    protected static function emailTableAction(string $type): TableAction
    {
        return TableAction::make('emailPdf')
            ->label('Email PDF')
            ->icon('heroicon-o-envelope')
            ->modalHeading('Email PDF to client')
            ->modalSubmitActionLabel('Send email')
            ->form(fn (Model $record): array => self::emailFormSchema($record, $type))
            ->action(fn (Model $record, array $data) => self::sendEmail($record, $data));
    }

    protected static function emailFormSchema(Model $record, string $type): array
    {
        $record->loadMissing('client');
        $label = match ($type) {
            'invoice' => 'Invoice',
            'quotation' => 'Quotation',
            'receipt' => 'Receipt',
            default => 'Document',
        };

        return [
            Forms\Components\TextInput::make('email')
                ->label('Recipient email')
                ->email()
                ->required()
                ->default($record->client?->email),
            Forms\Components\TextInput::make('subject')
                ->required()
                ->default("{$label} {$record->number} from Dispatch Logistics")
                ->maxLength(255),
            Forms\Components\Textarea::make('message')
                ->label('Message (optional)')
                ->rows(4)
                ->placeholder('Add a short note for the client...'),
        ];
    }

    protected static function sendEmail(Model $record, array $data): void
    {
        if (! $record instanceof Invoice && ! $record instanceof Quotation && ! $record instanceof Receipt) {
            Notification::make()
                ->danger()
                ->title('Unable to email this document')
                ->send();

            return;
        }

        $record->loadMissing('client');

        Mail::to($data['email'])->queue(new DocumentPdfMail(
            document: $record,
            recipientEmail: $data['email'],
            customSubject: $data['subject'] ?? null,
            customMessage: $data['message'] ?? null,
        ));

        Notification::make()
            ->success()
            ->title('Email queued')
            ->body("PDF will be sent to {$data['email']}.")
            ->send();
    }
}
