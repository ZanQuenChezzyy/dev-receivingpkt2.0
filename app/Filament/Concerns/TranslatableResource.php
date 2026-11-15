<?php

namespace App\Filament\Concerns;

use Filament\Resources\Resource;
use UnitEnum;

/**
 * @mixin Resource
 */
trait TranslatableResource
{
    /**
     * Get the translated navigation group label for this resource.
     */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $group = parent::getNavigationGroup();

        if ($group === null) {
            return null;
        }

        if ($group instanceof UnitEnum) {
            return $group;
        }

        return __($group);
    }
}
