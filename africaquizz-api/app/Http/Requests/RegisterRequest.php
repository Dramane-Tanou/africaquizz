<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'date_of_birth' => 'required|date|before:today',
            'parental_email' => 'nullable|email|required_if_age_between_13_and_15',
            'timezone' => 'nullable|string',
            'locale' => 'nullable|in:fr,en',
        ];
    }

    public function withValidator($validator) {
        $validator->after(function ($validator) {
            if ($this->date_of_birth) {
                $age = \Carbon\Carbon::parse($this->date_of_birth)->age;
                if ($age >= 13 && $age < 15 && empty($this->parental_email)) {
                    $validator->errors()->add('parental_email', 'Un email parental est requis pour les 13-15 ans.');
                }
            }
        });
    }
}