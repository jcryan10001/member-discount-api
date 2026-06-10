<?php

namespace Database\Seeders;

use App\Enums\Sector;
use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The three demo accounts the README documents, all with password "password".
 *
 * Deliberately uses explicit data rather than model factories: seeders run at
 * Docker build time under `composer install --no-dev`, where fakerphp/faker
 * (a dev dependency) isn't installed. forceFill sets the guarded
 * verification_status / is_admin columns; the model's "hashed" cast hashes the
 * password.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Primary login for the reviewer: a verified healthcare worker.
        $this->createMember('Nadia Nurse', 'nurse@demo.test', Sector::Healthcare, VerificationStatus::Verified);

        // Demonstrates the "unverified members can't redeem" path.
        $this->createMember('Pat Pending', 'pending@demo.test', Sector::Healthcare, VerificationStatus::Pending);

        // Admin: reviews verification requests, manages brands and offers.
        $this->createMember('Adam Admin', 'admin@demo.test', Sector::Healthcare, VerificationStatus::Verified, isAdmin: true);
    }

    private function createMember(
        string $name,
        string $email,
        Sector $sector,
        VerificationStatus $status,
        bool $isAdmin = false,
    ): void {
        (new User)->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'sector' => $sector,
            'verification_status' => $status,
            'is_admin' => $isAdmin,
        ])->save();
    }
}
