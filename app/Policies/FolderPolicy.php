<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Auth\Access\HandlesAuthorization;

class FolderPolicy extends BasePolicy
{
    use WriteLogs;

    use HandlesAuthorization;

    public function list(User $actor, User $user)
    {
        if ($actor->isSuperAdmin()) return true;

        return $actor->permitted('folder.list') && $actor->id == $user->id;
    }

    public function show(User $actor, User $owner, Folder $folder)
    {
        if ($actor->isSuperAdmin()) return true;

        return $owner->id == $folder->user_id;
    }

    /**
     * Determine whether the user can store models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function store(User $actor, User $owner)
    {
        $this->restrictDemo();

        if ($actor->isSuperAdmin()) return true;

        return $actor->permitted('folder.store') && $actor->id == $owner->id;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $actor, User $owner, Folder $folder)
    {
        if ($actor->isSuperAdmin()) return true;

        return $actor->permitted('folder.update') &&
            $actor->id == $owner->id &&
            $folder->user_id == $owner->id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function destroy(User $actor, User $owner, Folder $folder)
    {
        $this->restrictDemo();

        if ($actor->isSuperAdmin()) return true;

        return $actor->permitted('folder.destroy') &&
            $actor->id == $owner->id &&
            $folder->user_id == $owner->id;
    }
}
