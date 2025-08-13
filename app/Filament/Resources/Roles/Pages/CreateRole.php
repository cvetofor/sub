<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateRole extends CreateRecord {
    protected static string $resource = RoleResource::class;

    protected static bool $canCreateAnother = false;

    public function form(Schema $schema): Schema {
        return $schema->components([
            Grid::make(1)->schema([
                Section::make('Детали')->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $transliterated = $this->transliterateToLatin($state);
                            $set('code', $transliterated);
                        })
                        ->trim(),
                ])
            ])
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array {
        if (empty($data['code']) && !empty($data['name'])) {
            $data['code'] = $this->transliterateToLatin($data['name']);
        }

        return $data;
    }

    private function transliterateToLatin(string $text): string {
        $cyrillic = [
            'а',
            'б',
            'в',
            'г',
            'д',
            'е',
            'ё',
            'ж',
            'з',
            'и',
            'й',
            'к',
            'л',
            'м',
            'н',
            'о',
            'п',
            'р',
            'с',
            'т',
            'у',
            'ф',
            'х',
            'ц',
            'ч',
            'ш',
            'щ',
            'ъ',
            'ы',
            'ь',
            'э',
            'ю',
            'я',
            'А',
            'Б',
            'В',
            'Г',
            'Д',
            'Е',
            'Ё',
            'Ж',
            'З',
            'И',
            'Й',
            'К',
            'Л',
            'М',
            'Н',
            'О',
            'П',
            'Р',
            'С',
            'Т',
            'У',
            'Ф',
            'Х',
            'Ц',
            'Ч',
            'Ш',
            'Щ',
            'Ъ',
            'Ы',
            'Ь',
            'Э',
            'Ю',
            'Я'
        ];

        $latin = [
            'a',
            'b',
            'v',
            'g',
            'd',
            'e',
            'yo',
            'zh',
            'z',
            'i',
            'y',
            'k',
            'l',
            'm',
            'n',
            'o',
            'p',
            'r',
            's',
            't',
            'u',
            'f',
            'h',
            'ts',
            'ch',
            'sh',
            'sch',
            '',
            'y',
            '',
            'e',
            'yu',
            'ya',
            'A',
            'B',
            'V',
            'G',
            'D',
            'E',
            'Yo',
            'Zh',
            'Z',
            'I',
            'Y',
            'K',
            'L',
            'M',
            'N',
            'O',
            'P',
            'R',
            'S',
            'T',
            'U',
            'F',
            'H',
            'Ts',
            'Ch',
            'Sh',
            'Sch',
            '',
            'Y',
            '',
            'E',
            'Yu',
            'Ya'
        ];

        $text = str_replace($cyrillic, $latin, $text);

        $text = preg_replace('/[^\w]/u', '_', $text);
        $text = preg_replace('/_+/', '_', $text);
        $text = strtolower($text);

        return $text;
    }
}
