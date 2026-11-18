<?php

namespace App\Interfaces;

use App\Models\QRCode;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;

interface UserManager
{
    public function getSuperAdmins();

    public function deleteUser(User $user);

    public function changeRole(User $user, Role $role);

    public function getCurrentSubscription(User $user): ?Subscription;

    public function getCurrentPlan(User $user);

    public function inviteUser(
        User $actor,
        string $name,
        string $email,
        $mobileNumber,
        array $folderIds
    );

    public function subUsers(User $actor);

    public function deleteSubUser(User $subUser);

    public function getParentUser(User $subUser);

    public function getClientUser(QRCode $qrcode);

    public function getUserIdsOnTheSameSubscription(User $user);
}
