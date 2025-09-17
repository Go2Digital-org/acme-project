<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'in:EUR,USD,GBP'],
        ];
    }

    public function currency(): string
    {
        return $this->string('currency', 'EUR')->toString();
    }
}
