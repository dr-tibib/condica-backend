<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateCentralSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'central:create-super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a superadmin user in the central database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating central superadmin...');

        // 1. Ensure Role Exists
        $role = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'superadmin', 'guard_name' => 'web']
        );

        $this->info("Role 'superadmin' is ready.");

        // 2. Ask for User Details
        $name = $this->ask('Name', 'Super Admin');
        $email = $this->ask('Email', 'admin@example.com');
        $password = $this->secret('Password');
        $confirmPassword = $this->secret('Confirm Password');

        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match!');

            return 1;
        }

        // 3. Create User if not exists
        $user = \App\Models\CentralUser::where('email', $email)->first();

        if ($user) {
            if (! $this->confirm("User with email {$email} already exists. Do you want to assign the superadmin role to them?")) {
                $this->info('Operation cancelled.');

                return 0;
            }
        } else {
            $user = \App\Models\CentralUser::create([
                'name' => $name,
                'email' => $email,
                'password' => \Illuminate\Support\Facades\Hash::make($password),
            ]);
            $this->info("User {$name} created successfully.");
        }

        // 4. Assign Role
        if (! $user->hasRole('superadmin')) {
            $user->assignRole($role);
            $this->info("Role 'superadmin' assigned to {$user->email}.");
        } else {
            $this->info("User already has the 'superadmin' role.");
        }

        return 0;
    }
}
