<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class GoogleAuthRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        // Les champs de profil ne sont exigés qu'à la création du compte (voir AuthController::google)
        return [
            'credential' => 'required|string',
            'date_of_birth' => 'nullable|date|before:today',
            'country_id' => 'nullable|uuid|exists:countries,id',
            'parental_email' => 'nullable|email',
            'timezone' => 'nullable|string',
            'locale' => 'nullable|in:fr,en',
        ];
    }
}
