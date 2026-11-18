<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ErrorMessageMiddleware;
use Illuminate\Http\Request;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Interfaces\ModelIndex;
use App\Interfaces\UserManager;
use App\Models\Role;
use App\Rules\AppPassword;
use App\Support\Billing\AccountCreditBillingManager;
use App\Support\Billing\BillingManager;
use App\Support\FolderManager;
use App\Support\MobileNumberManager;
use App\Support\System\Traits\WriteLogs;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class UsersController extends Controller
{
    use WriteLogs;

    private UserManager $users;

    private FolderManager $folders;

    private AccountCreditBillingManager $accountCredit;

    private BillingManager $billing;

    public function __construct(UserManager $users)
    {
        $this->users = $users;

        $this->folders = new FolderManager;

        $this->billing = new BillingManager();

        $this->accountCredit = new AccountCreditBillingManager();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ModelIndex $index)
    {
        if ($request->list_all) {
            return User::all();
        }

        return $index->build(new User, $request)
            ->withQuery(function ($query) use ($request) {
                $query->with('transactions');
                $query->with('roles');
                $query->with('parent_user');

                if ($request->has('paying')) {

                    $paying = $request->boolean('paying');

                    if ($paying) {
                        $query->has('transactions');
                    } else {
                        $query->doesntHave('transactions');
                    }
                }
            })
            ->list(['name', 'email']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create(array_merge($request->all(), [
            'password' => Hash::make($request->password)
        ]));

        $user->markEmailAsVerified();

        $this->users->changeRole($user, Role::find($request->role_id));

        return $user;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return $user;
    }

    public function verifyEmail(User $user)
    {
        $user->markEmailAsVerified();

        return [
            'success' => true
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $shouldChangeRole = !empty($request->role_id) && $request->role_id != $user->roles->first()?->id;

        if ($shouldChangeRole) {
            $this->authorizeChangeRole($request, $user);
        }

        $user->fill($request->all());

        if ($request->password) {

            request()->validate([
                'password' => ['required', 'confirmed', new AppPassword]
            ]);

            $user->password = Hash::make($request->password);
        }

        $anotherUser = User::whereEmail($user->email)->first();

        if ($anotherUser) {
            $emailExists = $anotherUser->id !== $user->id;

            if ($emailExists) {
                throw ValidationException::withMessages([
                    'email' => ['This email is taken.'],
                ]);
            }
        }

        $user->save();

        if ($shouldChangeRole) {
            $this->users->changeRole($user, Role::findOrFail($request->role_id));
        }

        $user->refresh();

        return $user;
    }

    public function inviteSubUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'email' => ['required', 'email']
        ], [], [
            'folder_id' => t('Folder')
        ]);

        MobileNumberManager::extendValidator($validator);

        $validator->after(function ($validator) use ($request, $user) {
            if (User::whereEmail($request->email)->first()) {
                $validator->errors()->add('email', t('Email is taken by another user.'));
            }

            $allowedFolderIds = collect(
                $this->folders->list($user)
            )->pluck('id');

            $folderIds = $request->input('folder_id');

            $folderIds = collect($folderIds);

            $hasInvalidIds = $folderIds->filter(
                fn ($id) => $allowedFolderIds->first(fn ($aId) => $id == $aId)
            )->count() != $folderIds->count();

            if ($hasInvalidIds) {
                $validator->errors()->add('folder_id', t('Folder ID is invalid.'));
            }
        });

        $validator->validate();

        return $this->users->inviteUser(
            actor: $user,
            name: $request->name,
            mobileNumber: $request->mobile_number,
            email: $request->email,
            folderIds: $request->folder_id
        );
    }

    public function listSubUsers(User $user)
    {
        return $this->users->subUsers($user);
    }

    public function deleteSubUser(User $user, User $subUser)
    {
        return $this->users->deleteSubUser($subUser);
    }

    private function authorizeChangeRole(Request $request, User $user)
    {
        if (!$request->user()->permitted('user.update-any')) {
            ErrorMessageMiddleware::setMessage(t('This action is not authorized'));
            abort(403);
        }

        if ($user->id === $request->user()->id) {
            ErrorMessageMiddleware::setMessage(t('Cannot change role of logged in user.'));
            abort(422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $this->users->deleteUser($user);

        return $user;
    }

    public function changeAccountBalance(User $user)
    {
        if (!$this->billing->isAccountCreditBilling()) {
            abort(403, 'Account Credit billing mode is disabled');
        }

        $this->accountCredit
            ->forUser($user)
            ->setAccountBalance(
                request()->input('account_balance')
            );

        return [
            'account_balance' => $this->accountCredit->getAccountBalance()
        ];
    }

    public function getAccountBalance(User $user)
    {
        if (!$this->billing->isAccountCreditBilling()) {
            abort(403, 'Account Credit billing mode is disabled');
        }

        return [
            'account_balance' => $this->accountCredit->forUser($user)->getAccountBalance()
        ];
    }
}
