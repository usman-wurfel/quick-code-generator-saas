<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\UpdateFolderRequest;
use App\Models\Folder;
use App\Models\User;
use App\Support\FolderManager;

class FolderController extends Controller
{
    private FolderManager $folders;

    public function __construct()
    {
        $this->folders = new FolderManager;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        $folders = $this->folders->list($user);

        return $folders;
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreFolderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFolderRequest $request, User $user)
    {
        return $this->folders->saveFolder(
            $user,
            $request->folder_name
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function show(User $user, Folder $folder)
    {
        return $folder;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateFolderRequest  $request
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateFolderRequest $request, User $user, Folder $folder)
    {
        return $this->folders->saveFolder(
            $user,
            $request->folder_name,
            $folder->id,
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Folder  $folder
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user, Folder $folder)
    {
        return $this->folders->delete($folder);
    }
}
