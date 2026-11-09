<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Fulfilment')
                    ->description('Orders are created by customers on the storefront. Only the status can be changed here.')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                            ])
                            ->required()
                            ->default('pending'),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Customer & Delivery')
                    ->schema([
                        TextEntry::make('customer_name'),
                        TextEntry::make('phone')->copyable(),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => ucfirst($state))
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'shipped' => 'info',
                                'delivered' => 'success',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->label('Placed On')
                            ->dateTime('d M Y, h:i A'),
                        TextEntry::make('payment_method')
                            ->label('Payment Method')
                            ->formatStateUsing(fn (string $state): string => $state === 'cod' ? 'Cash on Delivery' : ucfirst($state)),
                        TextEntry::make('shipping_address')
                            ->label('Shipping Address')
                            ->state(fn (Order $record): string => "{$record->street}, {$record->area}, {$record->city}")
                            ->columnSpan(2),
                    ])
                    ->columns(3),
                Section::make('Order Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product_name'),
                                TextEntry::make('size')->placeholder('One size'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price')
                                    ->label('Price')
                                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state)),
                                TextEntry::make('line_total')
                                    ->label('Line Total')
                                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state)),
                            ])
                            ->columns(5),
                    ]),
                Section::make('Payment Summary')
                    ->schema([
                        TextEntry::make('subtotal')->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state)),
                        TextEntry::make('shipping_charge')
                            ->label('Shipping')
                            ->formatStateUsing(fn ($state) => (float) $state > 0
                                ? 'Rs. ' . number_format((float) $state)
                                : 'Complimentary'),
                        TextEntry::make('total')
                            ->label('Total Amount')
                            ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state)),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total Amount')
                    ->formatStateUsing(fn ($state) => 'Rs. ' . number_format((float) $state))
                    ->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                    ])
                    ->selectablePlaceholder(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
