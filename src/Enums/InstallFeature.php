<?php

namespace Onelegstudios\Tailor\Enums;

enum InstallFeature: string
{
    case UseLucideIcons = 'useLucideIcons';

    public function label(): string
    {
        return match ($this) {
            self::UseLucideIcons => 'Use Lucide icons',
        };
    }

    public function command(): string
    {
        return match ($this) {
            self::UseLucideIcons => 'tailor:use-lucide-icons',
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
