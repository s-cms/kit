<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class FileType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'file';
    }

    public function getDefaultValue(): mixed
    {
        return asset('favicon.ico');
    }

    public function getSchema(string $name, ?string $language = null): Field | Component
    {
        return FileUpload::make($name)->label(__('kit::admin.file'))->rules([
            'mimes:pdf,doc,docx,xls,xlsx,csv,xml,json,zip',
            'mimetypes:application/pdf,' .
                'application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,' .
                'application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,' .
                'text/csv,application/csv,text/plain,' .
                'application/xml,text/xml,' .
                'application/json,' .
                'application/zip,application/x-zip-compressed,multipart/x-zip',
            'max:10240',
        ]);
    }

    public function getValue(mixed $value): mixed
    {
        if (! $value || ! is_string($value)) {
            return $this->getDefaultValue();
        }

        return asset($value);
    }
}
