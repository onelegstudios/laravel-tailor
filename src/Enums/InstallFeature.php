<?php

namespace Onelegstudios\Tailor\Enums;

enum InstallFeature: string
{
    case Authentication = 'authentication';
    case Api = 'api';
    case Teams = 'teams';
    case Billing = 'billing';

    public function label(): string
    {
        return match ($this) {
            self::Authentication => 'Authentication scaffolding',
            self::Api => 'API scaffolding',
            self::Teams => 'Team management',
            self::Billing => 'Billing integration',
        };
    }

    public function command(): string
    {
        return match ($this) {
            self::Authentication => 'tailor:install-authentication',
            self::Api => 'tailor:install-api',
            self::Teams => 'tailor:install-teams',
            self::Billing => 'tailor:install-billing',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $feature) {
            $options[$feature->value] = $feature->label();
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $feature): string => $feature->value,
            self::cases(),
        );
    }
}
