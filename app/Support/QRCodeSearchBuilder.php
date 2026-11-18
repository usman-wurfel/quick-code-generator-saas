<?php

namespace App\Support;

use App\Models\QRCode;
use App\Models\User;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QRCodeSearchBuilder
{
    protected Builder $query;

    protected User $user;

    protected FolderManager $folders;

    protected $paginatePath;

    public function __construct()
    {
        $this->query = QRCode::query();

        $this->query->with('user');

        $this->folders = new FolderManager();
    }

    public function with($relations)
    {
        $this->query->with($relations);

        return $this;
    }

    public function paginationPath($path)
    {
        $this->paginatePath = $path;

        return $this;
    }

    public function paginate()
    {
        $paginated = $this->query->paginate(10);

        call_user_func([$paginated, 'withPath'], $this->paginatePath);

        return $paginated;
    }

    public function sort()
    {
        $this->query->orderBy('id', 'desc');

        return $this;
    }

    public function applySubUserRestrictions()
    {
        if (!$this->user->is_sub) return $this;

        $folderIds = $this->folders
            ->getSubuserFolders(
                $this->user
            )->pluck('id');

        $this->query->whereIn('folder_id', $folderIds);

        return $this;
    }

    public function applyClientRestrictions()
    {
        if ($this->user->isSuperAdmin()) return $this;

        if ($this->user->is_sub) return $this;

        $this->query->where(function ($query) {
            $query->where('user_id', $this->user->id);

            $subUsers = $this->user->sub_users;

            if (empty($subUsers)) return;

            $query->orWhereIn('user_id', $subUsers->pluck('id'));
        });

        return $this;
    }

    public function type($type)
    {
        if (empty($type)) return $this;

        $this->query->where('type', $type);

        return $this;
    }

    public function name($name)
    {
        if (empty($name)) return $this;

        $this->query->where(
            DB::raw('lower(name)'),
            'like',
            '%' . strtolower($name) . '%'
        );

        return $this;
    }

    public function folder($folderId)
    {
        if (empty($folderId)) return $this;

        $this->query->where('folder_id', $folderId);

        return $this;
    }

    public function archived($archived)
    {
        $this->query->whereArchived($archived);

        return $this;
    }

    public function byUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function forQrCodesCreatedBy($user_id)
    {
        if (empty($user_id)) return $this;

        $this->query->where('user_id', $user_id);

        return $this;
    }
}
