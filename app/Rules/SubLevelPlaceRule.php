<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SubLevelPlaceRule implements Rule
{
    protected int $subnivel;
    protected string $message = '';

    public function __construct(int $subnivel)
    {
        $this->subnivel = $subnivel;
    }

    public function passes($attribute, $value): bool
    {
        $codigo = $value;

        if (strlen($codigo) > 1 && $this->subnivel > 0) {

            // Opcional: validar que sea par
            if (strlen($codigo) % 2 !== 0) {
                $this->message = "El código debe tener una longitud par.";
                return false;
            }

            $expected = strlen($codigo) / 2;

            if ($expected !== $this->subnivel) {
                $this->message = "Si el código tiene una longitud de "
                    . strlen($codigo)
                    . ", el subnivel debe ser "
                    . $expected;

                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->message ?: 'El formato del código es inválido.';
    }
}
