<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantSuperAdmin extends Command
{
    protected $signature = 'app:superadmin {email} {--revoke}';

    protected $description = 'Asigna o revoca permisos de plataforma a una cuenta existente.';

    public function handle(): int
    {
        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($this->argument('email'))])->first();
        if (! $user) {
            $this->error('La cuenta no existe. Regístrala antes de asignar permisos.');

            return self::FAILURE;
        }
        $user->forceFill(['is_superadmin' => ! $this->option('revoke')])->save();
        $this->info($this->option('revoke') ? 'Permiso de superadministrador revocado.' : 'Permiso de superadministrador asignado.');

        return self::SUCCESS;
    }
}
