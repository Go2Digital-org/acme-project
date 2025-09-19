<?php

declare(strict_types=1);

namespace Modules\Organization\Application\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final class CreateOrganizationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in processor if needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'registration_number' => ['nullable', 'string', 'max:100', 'unique:organizations,registration_number'],
            'tax_id' => ['nullable', 'string', 'max:100', 'unique:organizations,tax_id'],
            'category' => ['required', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:organizations,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Organization name is required.',
            'name.unique' => 'Organization name already exists.',
            'registration_number.unique' => 'Registration number already exists.',
            'tax_id.unique' => 'Tax ID already exists.',
            'category.required' => 'Organization category is required.',
            'website.url' => 'Website must be a valid URL.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Email must be a valid email address.',
            'email.unique' => 'Email address already exists.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                errors: $validator->errors()->toArray(),
                message: 'Organization creation validation failed.',
            ),
        );
    }
}
