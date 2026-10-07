<?php

namespace App\Enums;

/** Roles de la plataforma. `users.role` guarda el valor (string) para no romper `User::hasRole()`. */
enum Role: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Propietario = 'propietario';
    case Residente = 'residente';
    case Seguridad = 'seguridad';
    case Mantenimiento = 'mantenimiento';
    case Junta = 'junta';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadministrador',
            self::Admin => 'Administración',
            self::Propietario => 'Propietario',
            self::Residente => 'Residente',
            self::Seguridad => 'Seguridad',
            self::Mantenimiento => 'Mantenimiento',
            self::Junta => 'Junta directiva',
        };
    }

    /** Ven la operación completa de la comunidad (no solo sus unidades). */
    public function seesEverything(): bool
    {
        return in_array($this, [self::Superadmin, self::Admin, self::Junta], true);
    }

    /** Usuarios ligados a un apartamento: su visibilidad se limita a sus unidades. */
    public function isResident(): bool
    {
        return in_array($this, [self::Propietario, self::Residente], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
