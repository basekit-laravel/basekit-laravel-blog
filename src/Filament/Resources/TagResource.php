<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources;

use BackedEnum;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\TagResource\Pages;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagResource extends Resource
{
    /** @var class-string<Tag> */
    protected static ?string $model = Tag::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return __('basekit-laravel-blog::basekit-laravel-blog.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('basekit-laravel-blog::basekit-laravel-blog.tag');
    }

    public static function getPluralModelLabel(): string
    {
        return __('basekit-laravel-blog::basekit-laravel-blog.tags');
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
            'create' => Pages\CreateTag::route('/create'),
            'edit' => Pages\EditTag::route('/{record}/edit'),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        $locales = LocaleRegistry::fromConfig()->all();

        return $schema->components([
            Section::make(__('basekit-laravel-blog::basekit-laravel-blog.translations'))
                ->schema([
                    Repeater::make('translations')
                        ->hiddenLabel()
                        ->addActionLabel(__('basekit-laravel-blog::basekit-laravel-blog.add_translation'))
                        ->default(array_map(
                            fn (string $locale): array => ['locale' => $locale],
                            $locales,
                        ))
                        ->reorderable(false)
                        ->columns(2)
                        ->schema([
                            Select::make('locale')
                                ->label(__('basekit-laravel-blog::basekit-laravel-blog.locale'))
                                ->options(array_combine($locales, $locales))
                                ->required()
                                ->distinct(),
                            TextInput::make('name')
                                ->label(__('basekit-laravel-blog::basekit-laravel-blog.name'))
                                ->required()
                                ->maxLength(255),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.name'))
                    ->getStateUsing(fn (Tag $record): string => $record->name())
                    ->searchable(['translations.name'])
                    ->sortable(),
                TextColumn::make('locales')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.locales'))
                    ->badge()
                    ->getStateUsing(fn (Tag $record): array => $record->translations->pluck('locale')->all())
                    ->toggleable(),
                TextColumn::make('posts_count')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.posts'))
                    ->counts('posts')
                    ->badge(),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                ForceDeleteAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ForceDeleteBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
