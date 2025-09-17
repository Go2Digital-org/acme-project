<?php

declare(strict_types=1);

namespace App\Models;

use Modules\User\Infrastructure\Laravel\Models\User as BaseUser;

/**
 * User model facade for legacy Laravel app structure
 *
 * This class acts as a bridge between the old app structure
 * and the new hexagonal architecture User model.
 */
class User extends BaseUser
{
    // This class inherits all functionality from the hexagonal User model
    // No additional methods needed - just provides the App\Models\User namespace
}
