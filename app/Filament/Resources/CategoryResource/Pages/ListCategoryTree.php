<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions; // Pastikan namespace ini diimpor
use Filament\Resources\Pages\Page; // Halaman kustom mewarisi dari Filament\Resources\Pages\Page

class ListCategoryTree extends Page
{
    protected static string $resource = CategoryResource::class;
    protected static string $view = 'filament.resources.category-resource.pages.list-category-tree';
    protected static ?string $title = 'Category Tree';

    // Tambahkan metode ini untuk mendefinisikan aksi header
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Category') // Ini akan membuat tombol "New Category"
                ->url(CategoryResource::getUrl('create')), // Pastikan tombol mengarah ke halaman 'create' resource
        ];
    }
}
