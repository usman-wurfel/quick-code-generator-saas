<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class FilePolicy
{
    use HandlesAuthorization;

    public function show(User $user, File $file)
    {
        if ($user->permitted('file.show-any')) {
            return true;
        }

        return $user->permitted('file.show') && $user->id == $file->user_id;
    }

    public function destroy(User $user, File $file)
    {
        if ($user->permitted('file.destroy-any')) {
            return true;
        }

        return $user->permitted('file.destroy') && $user->id == $file->user_id;
    }
}
