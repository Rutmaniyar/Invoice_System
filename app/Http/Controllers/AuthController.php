<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Services\AuditLogger;
use App\Services\MailerService;
use App\Services\RateLimiter;

final class AuthController extends Controller
{
    public function login(): string
    {
        return $this->view('auth/login', ['title' => 'Sign in'], 'layouts/guest');
    }

    public function authenticate(Request $request): never
    {
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $limiterKey = 'login:' . hash('sha256', $email . '|' . $request->ip());
        $limiter = new RateLimiter();
        $maxAttempts = (int) config('security.login_attempts', 5);

        if ($limiter->tooManyAttempts($limiterKey, $maxAttempts)) {
            Session::flash('errors', ['email' => 'Too many attempts. Try again later.']);
            $this->redirect('/login');
        }

        if (!Auth::attempt($email, (string) $request->input('password', ''))) {
            $limiter->hit($limiterKey, (int) config('security.login_decay_minutes', 15));
            AuditLogger::log('auth.login_failed', 'user', null, ['email' => $email]);
            Session::flash('errors', ['email' => 'The provided credentials are invalid.']);
            Session::flash('_old', ['email' => $email]);
            $this->redirect('/login');
        }

        $limiter->clear($limiterKey);
        AuditLogger::log('auth.login');
        $this->redirect('/dashboard');
    }

    public function logout(): never
    {
        AuditLogger::log('auth.logout');
        Auth::logout();
        $this->redirect('/login');
    }

    public function forgot(): string
    {
        return $this->view('auth/forgot', ['title' => 'Reset password'], 'layouts/guest');
    }

    public function sendReset(Request $request): never
    {
        $data = $request->all();
        $validator = (new Validator($data))->required('email', 'Email')->email('email', 'Email');
        if ($validator->fails()) {
            Session::flash('errors', $validator->errors());
            $this->redirect('/forgot-password');
        }

        $user = app()->db()->fetch('SELECT id, email, name FROM users WHERE email = ? AND deleted_at IS NULL', [mb_strtolower(trim((string) $data['email']))]);
        if ($user) {
            $token = bin2hex(random_bytes(32));
            app()->db()->execute(
                'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
                [$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]
            );
            $link = base_url('/reset-password?token=' . $token . '&email=' . urlencode($user['email']));
            (new MailerService())->send($user['email'], 'Reset your LedgerFlow password', "Hello {$user['name']},\n\nUse this secure link to reset your password:\n{$link}\n\nThis link expires in one hour.");
        }

        Session::flash('success', 'If that email exists, a password reset link has been sent.');
        $this->redirect('/forgot-password');
    }

    public function reset(Request $request): string
    {
        return $this->view('auth/reset', [
            'title' => 'Choose a new password',
            'token' => (string) $request->input('token', ''),
            'email' => (string) $request->input('email', ''),
        ], 'layouts/guest');
    }

    public function updatePassword(Request $request): never
    {
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $tokenHash = hash('sha256', (string) $request->input('token', ''));
        $password = (string) $request->input('password', '');

        if ($password !== (string) $request->input('password_confirmation', '') || strlen($password) < 10) {
            Session::flash('errors', ['password' => 'Password must be at least 10 characters and match confirmation.']);
            $this->redirect('/forgot-password');
        }

        $row = app()->db()->fetch(
            'SELECT password_resets.*, users.email FROM password_resets
             INNER JOIN users ON users.id = password_resets.user_id
             WHERE users.email = ? AND token_hash = ? AND used_at IS NULL AND expires_at > NOW()
             ORDER BY password_resets.id DESC LIMIT 1',
            [$email, $tokenHash]
        );

        if (!$row) {
            Session::flash('errors', ['token' => 'The password reset link is invalid or expired.']);
            $this->redirect('/forgot-password');
        }

        app()->db()->transaction(function () use ($row, $password): void {
            app()->db()->execute('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
            app()->db()->execute('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$row['id']]);
        });

        Session::flash('success', 'Password updated. Sign in with your new password.');
        $this->redirect('/login');
    }

    public function verifyEmail(Request $request): never
    {
        $email = mb_strtolower(trim((string) $request->input('email', '')));
        $tokenHash = hash('sha256', (string) $request->input('token', ''));
        $row = app()->db()->fetch(
            'SELECT email_verifications.*, users.email
             FROM email_verifications INNER JOIN users ON users.id = email_verifications.user_id
             WHERE users.email = ? AND token_hash = ? AND used_at IS NULL AND expires_at > NOW()
             ORDER BY email_verifications.id DESC LIMIT 1',
            [$email, $tokenHash]
        );

        if (!$row) {
            Session::flash('errors', ['token' => 'The verification link is invalid or expired.']);
            $this->redirect('/login');
        }

        app()->db()->transaction(function () use ($row): void {
            app()->db()->execute('UPDATE users SET email_verified_at = NOW(), updated_at = NOW() WHERE id = ?', [$row['user_id']]);
            app()->db()->execute('UPDATE email_verifications SET used_at = NOW() WHERE id = ?', [$row['id']]);
        });

        Session::flash('success', 'Email verified. You can now sign in.');
        $this->redirect('/login');
    }
}
