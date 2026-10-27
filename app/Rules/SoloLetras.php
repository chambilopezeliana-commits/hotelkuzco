<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida que un texto esté compuesto únicamente por letras (incluye acentos
 * y la letra ñ) separadas por espacios simples. No admite números ni signos.
 */
class SoloLetras implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if (! preg_match("/^[\p{L}]+(?:[ '\-]+[\p{L}]+)*$/u", trim($value))) {
            $fail('Este campo solo debe contener letras, espacios, apostrofes y guiones.');
        }
    }
}
