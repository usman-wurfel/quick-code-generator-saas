<?php

namespace App\Http\Controllers;

use App\Interfaces\SubscriptionManager;
use App\Interfaces\UserManager;
use App\Models\Config;

use App\Models\User;

use App\Rules\AppPassword;
use App\Support\Auth\AuthManager;
use App\Support\Billing\AccountCreditBillingManager;
use App\Support\Billing\BillingManager;
use Illuminate\Http\Request;

use App\Support\MobileNumberManager;

use App\Support\Security\AccountSecurityManager;

use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Password;

use Illuminate\Auth\Events\Registered;

use Illuminate\Auth\Events\Verified;

use Illuminate\Auth\Notifications\ResetPassword;

use Illuminate\Auth\Events\PasswordReset;

use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    private AccountCreditBillingManager $accountCredit;

    private BillingManager $billing;

    private UserManager $users;

    private SubscriptionManager $subscriptions;

    public function __construct()
    {
        $this->accountCredit = app(AccountCreditBillingManager::class);
        $this->billing = app(BillingManager::class);
        $this->users = app(UserManager::class);
        $this->subscriptions  = app(SubscriptionManager::class);
    }

    public function register(Request $request)
    {
        $registrationDisabled = Config::get('app.new_user_registration') == 'disabled';

        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:App\Models\User',
            'password' => ['required', 'confirmed', new AppPassword]
        ];

        $validator = Validator::make($request->all(), $registrationDisabled ? [] : $rules);

        MobileNumberManager::extendValidator($validator);

        $validator->after(function ($validator) use ($registrationDisabled) {
            if ($registrationDisabled) {
                $validator->errors()->add('email', t('New registrations are disabled.'));
            }
        });

        $validator->validate();

        $user = User::create(array_merge($request->all(), [
            'password' => Hash::make($request->password)
        ]));

        if (!AuthManager::emailVerificationEnabled()) {
            $user->email_verified_at = now();
            $user->save();
        }

        // Sends the verification email
        event(new Registered($user));

        return [
            'token' => $user->createToken('spa')->plainTextToken
        ];
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return [
            'sent' => true
        ];
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $securityManager = new AccountSecurityManager($request);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            $securityManager->handleFailedLoginAttempt();
        }

        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => [t('You must verify your email before login')]
            ]);
        }

        $securityManager->handleLoginAttemptWithWorkingCredentials();

        $user = $this->getUser($user->id);

        return [
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => $user
        ];
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::find($id);

        if (!$user) {
            abort(403, 'Invalid user');
        }

        if (!hash_equals(
            (string) $request->route('hash'),
            sha1($user->getEmailForVerification())
        )) {
            abort(403, 'Invalid hash code');
        }

        if (!$user->hasVerifiedEmail()) {

            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return redirect(
            config('frontend.url') . '/account/email-verified'
        );
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            $email = $notifiable->getEmailForPasswordReset();

            return config('frontend.url')
                . "/account/reset-password?token=$token&email=$email";
        });

        Password::sendResetLink(
            $request->only('email')
        );

        return [
            'success' => 1
        ];
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', new AppPassword],
        ]);


        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {

                $user->forceFill([
                    'password' => Hash::make($password)
                ]);

                $user->save();

                $securityManager = new AccountSecurityManager($request);

                $securityManager->clearAccountLock();

                event(new PasswordReset($user));
            }
        );

        return compact('status');
    }

    public function myself(Request $request)
    {
        return $this->getUser($request->user()->id);
    }

    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        $subscription = $this->users->getCurrentSubscription($user);

        $this->subscriptions->cancelSubscription($subscription);

        return $subscription;
    }

    public function getUser($userId)
    {
        $user = User::with(
            'subscriptions',
            'subscriptions.subscription_plan',
            'subscriptions.statuses',
            'parent_user.subscriptions',
            'parent_user.subscriptions.subscription_plan',
            'parent_user.subscriptions.statuses',
        )->find(
            $userId
        );

        if ($this->billing->isAccountCreditBilling()) {
            $user->account_balance = $this->accountCredit->forUser($user)->getAccountBalance();
        }

        return $user;
    }

    public function actAs(User $user, Request $request)
    {
        if (!$request->user()->isSuperAdmin()) {
            abort(403);
        }

        $user = $this->getUser($user->id);

        return [
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => $user
        ];
    }
}
