<?php

declare(strict_types=1);

namespace BasekitLaravel\BasekitLaravelBlog\Filament\Resources;

use BackedEnum;
use BasekitLaravel\BasekitLaravelBlog\Enums\PostStatus;
use BasekitLaravel\BasekitLaravelBlog\Filament\Resources\PostResource\Pages;
use BasekitLaravel\BasekitLaravelBlog\Filament\Support\TaxonomyOptions;
use BasekitLaravel\BasekitLaravelBlog\Models\Category;
use BasekitLaravel\BasekitLaravelBlog\Models\Post;
use BasekitLaravel\BasekitLaravelBlog\Models\Tag;
use BasekitLaravel\BasekitLaravelBlog\Support\BlogUrl;
use BasekitLaravel\BasekitLaravelBlog\Support\LocaleRegistry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The admin screen for posts. Every locale of a post is edited on one form as
 * a repeater of translations, which is the shape the write actions accept.
 */
class PostResource extends Resource
{
    /** @var class-string<Post> */
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('basekit-laravel-blog::basekit-laravel-blog.navigation_group');
    }

    public static function getModelLabel(): string
    {
        return __('basekit-laravel-blog::basekit-laravel-blog.post');
    }

    public static function getPluralModelLabel(): string
    {
        return __('basekit-laravel-blog::basekit-laravel-blog.posts');
    }

    /** @return array<string, mixed> */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Post>
     */
    public static function getEloquentQuery(): Builder
    {
        return Post::query()
            ->with(['translations', 'author'])
            ->withCount('translations');
    }

    public static function getRecordTitle(?Model $record): ?string
    {
        if (! $record instanceof Post) {
            return null;
        }

        return $record->translation(LocaleRegistry::fromConfig()->default)->title
            ?? __('basekit-laravel-blog::basekit-laravel-blog.untitled');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = LocaleRegistry::fromConfig()->all();

        return $schema->components([
            Section::make(__('basekit-laravel-blog::basekit-laravel-blog.details'))
                ->schema(self::detailsSchema())
                ->columns(2),
            Section::make(__('basekit-laravel-blog::basekit-laravel-blog.taxonomy'))
                ->schema(self::taxonomySchema())
                ->columns(2),
            Section::make(__('basekit-laravel-blog::basekit-laravel-blog.translations'))
                ->description(__('basekit-laravel-blog::basekit-laravel-blog.translations_description'))
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
                        ->schema(self::translationSchema($locales)),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $defaultLocale = LocaleRegistry::fromConfig()->default;
        $locales = LocaleRegistry::fromConfig()->all();

        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.title'))
                    ->getStateUsing(fn (Post $record): string => (string) static::getRecordTitle($record))
                    ->searchable(['translations.title'])
                    ->sortable(),
                TextColumn::make('locales')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.locales'))
                    ->badge()
                    ->getStateUsing(fn (Post $record): array => $record->translations->pluck('locale')->all())
                    ->toggleable(),
                TextColumn::make('author.name')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.author'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.status'))
                    ->badge()
                    ->getStateUsing(fn (Post $record) => $record->translation($defaultLocale)?->status)
                    ->formatStateUsing(fn (?PostStatus $state): string => $state->value ?? '—'),
                TextColumn::make('published_at')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.published_at'))
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.status'))
                    ->options(PostStatus::class)
                    ->schema([
                        Select::make('locale')
                            ->label(__('basekit-laravel-blog::basekit-laravel-blog.locale'))
                            ->options(array_combine($locales, $locales))
                            ->default($defaultLocale),
                    ])
                    ->query(function (Builder $query, array $state) use ($defaultLocale): Builder {
                        $values = (array) ($state['value'] ?? []);

                        if ($values === []) {
                            return $query;
                        }

                        return $query->whereHas('translations', fn (Builder $query) => $query
                            ->where('locale', (string) ($state['locale'] ?? $defaultLocale))
                            ->whereIn('status', $values));
                    }),
                SelectFilter::make('locale')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.locale'))
                    ->options(array_combine($locales, $locales))
                    ->query(function (Builder $query, array $state): Builder {
                        $locale = (string) ($state['value'] ?? '');

                        if ($locale === '') {
                            return $query;
                        }

                        return $query->whereHas(
                            'translations',
                            fn (Builder $query) => $query->where('locale', $locale),
                        );
                    }),
                TrashedFilter::make(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                EditAction::make(),
                Action::make('preview')
                    ->label(__('basekit-laravel-blog::basekit-laravel-blog.preview'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Post $record): string => self::previewUrl($record) ?? url('/'))
                    ->openUrlInNewTab()
                    ->visible(fn (Post $record): bool => self::previewUrl($record) !== null),
                RestoreAction::make(),
                ForceDeleteAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function detailsSchema(): array
    {
        $userModel = (string) config('basekit-laravel-blog.models.user');

        return [
            Select::make('author_id')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.author'))
                ->options(fn (): array => $userModel::query()
                    ->orderBy('name')
                    ->pluck('name', (new $userModel)->getKeyName())
                    ->all())
                ->searchable()
                ->preload(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function taxonomySchema(): array
    {
        return [
            Select::make('category_ids')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.categories'))
                ->options(fn (): array => TaxonomyOptions::make(Category::class))
                ->getSearchResultsUsing(fn (string $search): array => TaxonomyOptions::make(Category::class, $search))
                ->multiple()
                ->preload(),
            Select::make('tag_ids')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.tags'))
                ->options(fn (): array => TaxonomyOptions::make(Tag::class))
                ->getSearchResultsUsing(fn (string $search): array => TaxonomyOptions::make(Tag::class, $search))
                ->multiple()
                ->preload(),
        ];
    }

    /**
     * @param  list<string>  $locales
     * @return array<int, mixed>
     */
    private static function translationSchema(array $locales): array
    {
        return [
            Select::make('locale')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.locale'))
                ->options(array_combine($locales, $locales))
                ->required()
                ->distinct(),
            Select::make('status')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.status'))
                ->options(PostStatus::class)
                ->default(PostStatus::Draft->value)
                ->required(),
            TextInput::make('title')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.title'))
                ->required()
                ->maxLength(255)
                ->columnSpan(2),
            Textarea::make('excerpt')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.excerpt'))
                ->rows(3)
                ->maxLength(2000)
                ->columnSpan(2),
            RichEditor::make('content')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.content'))
                ->columnSpan(2),
            DateTimePicker::make('published_at')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.published_at'))
                ->seconds(false)
                ->required(fn (Get $get): bool => self::needsPublishedAt($get('status'))),
            TextInput::make('slug')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.slug'))
                ->maxLength(255)
                ->helperText(__('basekit-laravel-blog::basekit-laravel-blog.slug_hint')),
            TextInput::make('featured_image')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.featured_image'))
                ->maxLength(2048),
            TextInput::make('image_alt')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.image_alt'))
                ->maxLength(255),
            TextInput::make('meta_title')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.meta_title'))
                ->maxLength(255),
            Textarea::make('meta_description')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.meta_description'))
                ->rows(2)
                ->maxLength(1000),
            Toggle::make('is_featured')
                ->label(__('basekit-laravel-blog::basekit-laravel-blog.is_featured')),
        ];
    }

    /**
     * A published or scheduled translation is dated; a draft is not.
     */
    private static function needsPublishedAt(mixed $status): bool
    {
        if ($status instanceof BackedEnum) {
            $status = $status->value;
        }

        return in_array(
            (string) $status,
            [PostStatus::Scheduled->value, PostStatus::Published->value],
            true,
        );
    }

    public static function previewUrl(Post $record): ?string
    {
        $locale = LocaleRegistry::fromConfig()->default;
        $url = app(BlogUrl::class);

        if (! $url->serves($locale) || $record->slug($locale) === null) {
            return null;
        }

        return $url->post($record, $locale);
    }
}
