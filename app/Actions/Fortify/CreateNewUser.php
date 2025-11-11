<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'branch' => ['nullable', 'string', 'max:255'],
            'directorate' => ['nullable', 'string', 'max:255'],
            'transport' => ['nullable', 'string', 'max:255'],
            'distance' => ['nullable', 'numeric', 'min:0'],
            'work_mode' => ['nullable', 'string', 'in:WFO,WFH'],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'cabang' => $input['branch'] ?? null,
            'direktorat' => $input['directorate'] ?? null,
            'kendaraan_kantor' => $input['transport'] ?? null,
            'jarak_rumah' => $input['distance'] ?? null,
            'mode_kerja' => $input['work_mode'] ?? 'WFO',
            'user_level' => 1,
        ]);
    }
}
