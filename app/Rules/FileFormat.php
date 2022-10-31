<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileFormat implements Rule
{
    const notAllowedFormats = ['php'];

    public function passes($attribute, $value): bool
    {
        if (!($value instanceof UploadedFile) || !$value->isValid()) {
            return false;
        }

        return !in_array($value->getClientOriginalExtension(), self::notAllowedFormats);
    }

    public function message(): string
    {
        return 'This format is not supported.';
    }
}
