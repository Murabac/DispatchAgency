<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Models\Tax;
use Database\Seeders\BusinessSettingsSeeder;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.manage-settings';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::query()->first();

        if (! $settings) {
            (new BusinessSettingsSeeder)->run();
            $settings = Setting::query()->firstOrFail();
        }

        $this->form->fill([
            'business_name' => $settings->business_name,
            'address' => $settings->address,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'logo_path' => $settings->logo_path,
            'bank_details' => $settings->bank_details,
            'invoice_prefix' => $settings->invoice_prefix,
            'quotation_prefix' => $settings->quotation_prefix,
            'receipt_prefix' => $settings->receipt_prefix,
            'next_invoice_number' => $settings->next_invoice_number,
            'next_quotation_number' => $settings->next_quotation_number,
            'next_receipt_number' => $settings->next_receipt_number,
            'taxes' => Tax::query()->orderBy('sort_order')->orderBy('id')->get()->map(fn (Tax $tax) => [
                'id' => $tax->id,
                'name' => $tax->name,
                'rate' => $tax->rate,
                'is_active' => $tax->is_active,
                'sort_order' => $tax->sort_order,
            ])->all(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Business details')
                    ->description('Shown on invoices, quotations, receipts, and emails.')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('logos')
                            ->disk('public')
                            ->imagePreviewHeight('80')
                            ->maxSize(2048)
                            ->helperText('Used on PDFs and the admin header. PNG recommended.'),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('address')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('bank_details')
                            ->label('Bank details')
                            ->rows(2)
                            ->helperText('Shown on invoices and receipts.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Taxes')
                    ->description('Active taxes are applied automatically to new quotations and invoices.')
                    ->schema([
                        Forms\Components\Repeater::make('taxes')
                            ->label('')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\TextInput::make('rate')
                                    ->label('Rate %')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->suffix('%'),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->inline(false),
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->addActionLabel('Add tax')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Document numbering')
                    ->description('Prefixes and the next number that will be used.')
                    ->schema([
                        Forms\Components\TextInput::make('invoice_prefix')
                            ->label('Invoice prefix')
                            ->required()
                            ->maxLength(20)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('next_invoice_number')
                            ->label('Next invoice #')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live(onBlur: true),
                        Forms\Components\Placeholder::make('next_invoice_preview')
                            ->label('Will create')
                            ->content(fn (Forms\Get $get): string => ($get('invoice_prefix') ?: 'INV-') . str_pad((string) ((int) $get('next_invoice_number') ?: 1), 5, '0', STR_PAD_LEFT)),

                        Forms\Components\TextInput::make('quotation_prefix')
                            ->label('Quotation prefix')
                            ->required()
                            ->maxLength(20)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('next_quotation_number')
                            ->label('Next quotation #')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live(onBlur: true),
                        Forms\Components\Placeholder::make('next_quotation_preview')
                            ->label('Will create')
                            ->content(fn (Forms\Get $get): string => ($get('quotation_prefix') ?: 'QUO-') . str_pad((string) ((int) $get('next_quotation_number') ?: 1), 5, '0', STR_PAD_LEFT)),

                        Forms\Components\TextInput::make('receipt_prefix')
                            ->label('Receipt prefix')
                            ->required()
                            ->maxLength(20)
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('next_receipt_number')
                            ->label('Next receipt #')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->live(onBlur: true),
                        Forms\Components\Placeholder::make('next_receipt_preview')
                            ->label('Will create')
                            ->content(fn (Forms\Get $get): string => ($get('receipt_prefix') ?: 'RCT-') . str_pad((string) ((int) $get('next_receipt_number') ?: 1), 5, '0', STR_PAD_LEFT)),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = Setting::query()->firstOrFail();

        $logoPath = $data['logo_path'] ?? null;
        if (is_array($logoPath)) {
            $logoPath = $logoPath[0] ?? null;
        }

        $settings->update([
            'business_name' => $data['business_name'],
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'logo_path' => $logoPath,
            'bank_details' => $data['bank_details'] ?? null,
            'invoice_prefix' => $data['invoice_prefix'],
            'quotation_prefix' => $data['quotation_prefix'],
            'receipt_prefix' => $data['receipt_prefix'],
            'next_invoice_number' => (int) $data['next_invoice_number'],
            'next_quotation_number' => (int) $data['next_quotation_number'],
            'next_receipt_number' => (int) $data['next_receipt_number'],
        ]);

        $this->syncLogoToPublic($logoPath);
        $this->syncTaxes($data['taxes'] ?? []);

        Notification::make()
            ->success()
            ->title('Settings saved')
            ->send();

        $this->mount();
    }

    protected function syncLogoToPublic(?string $logoPath): void
    {
        if (! $logoPath) {
            return;
        }

        $source = storage_path('app/public/' . ltrim($logoPath, '/'));
        if (! is_file($source)) {
            return;
        }

        $destination = public_path('images/logo.png');
        File::ensureDirectoryExists(dirname($destination));
        File::copy($source, $destination);
    }

    protected function syncTaxes(array $taxes): void
    {
        $keptIds = [];

        foreach (array_values($taxes) as $index => $taxData) {
            $payload = [
                'name' => $taxData['name'],
                'rate' => $taxData['rate'],
                'is_active' => (bool) ($taxData['is_active'] ?? true),
                'sort_order' => (int) ($taxData['sort_order'] ?? $index),
            ];

            if (! empty($taxData['id'])) {
                $tax = Tax::query()->find($taxData['id']);
                if ($tax) {
                    $tax->update($payload);
                    $keptIds[] = $tax->id;
                    continue;
                }
            }

            $keptIds[] = Tax::query()->create($payload)->id;
        }

        Tax::query()->whereNotIn('id', $keptIds)->delete();
    }
}
