<?php

namespace Elcreator\aSocialAuth\Support;

use Elcreator\aSocialAuth\Exceptions\SocialAuthException;
use Elcreator\aSocialAuth\Models\EmailVerification;

/**
 * Adding and proving an e-mail address after the fact.
 *
 * This is what makes a Telegram sign-up a complete account. Registering through
 * a provider that returns no e-mail leaves the user with a synthesised
 * `@social.invalid` address: fine for signing in, useless for everything else —
 * no password recovery, no notifications, and not fit for a manager role. The
 * owner adds a real address here and proves it by following a link.
 *
 * The claim is held in `social_email_verifications` and only written to
 * `user_attributes.email` on confirmation. That ordering is the security of the
 * whole feature: an unproven address in the user row would be enough to collect
 * someone else's provider identity through `linking.match_by_email`, and enough
 * to stop the rightful owner from ever registering it.
 *
 * Confirmation also flips `user_attributes.verified`, using the core's own
 * `\UserManager::verified()` so anything else watching that flag agrees.
 */
class EmailVerifier
{
    /**
     * Claim an address for a user and send them the proof link.
     *
     * @throws SocialAuthException
     */
    public static function request(int $userId, string $email, string $context): void
    {
        $email = strtolower(trim($email));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw SocialAuthException::lexicon('error_email_invalid');
        }

        if (UserResolver::isPlaceholderEmail($email)) {
            // The placeholder domain is ours; letting someone claim an address
            // in it would make a synthesised address indistinguishable from a
            // proven one.
            throw SocialAuthException::lexicon('error_email_invalid');
        }

        $owner = Credentials::findUserByEmail($email);

        if ($owner !== null) {
            if ((int) $owner->getKey() === $userId) {
                throw SocialAuthException::lexicon('error_email_already_yours');
            }

            throw SocialAuthException::lexicon('error_email_taken');
        }

        $token = static::generateToken();

        try {
            // One outstanding claim per user: starting a new one abandons the
            // old, so an address typed by mistake cannot be confirmed later.
            EmailVerification::query()->where('user_id', $userId)->delete();

            EmailVerification::query()->create([
                'user_id'    => $userId,
                'email'      => $email,
                'token'      => $token,
                'expires_at' => date('Y-m-d H:i:s', time() + Config::emailVerificationTtl() * 60),
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to record an e-mail claim for user #{$userId}: " . $e->getMessage());

            throw SocialAuthException::lexicon('error_generic');
        }

        if (!static::sendVerificationMail($email, $token, $context)) {
            // Nothing was proven and nothing was changed, so say so rather than
            // leaving the user waiting for a message that will not arrive.
            throw SocialAuthException::lexicon('error_email_send');
        }

        Log::info("User #{$userId} claimed an e-mail address; verification sent.");
    }

    /**
     * Prove a claim and move the address onto the user.
     *
     * @return array{user_id: int, email: string}
     * @throws SocialAuthException
     */
    public static function confirm(string $token): array
    {
        $token = trim($token);

        try {
            $claim = EmailVerification::findByToken($token);
        } catch (\Throwable $e) {
            Log::error('Failed to read an e-mail claim: ' . $e->getMessage());

            throw SocialAuthException::lexicon('error_generic');
        }

        if ($claim === null || $claim->hasExpired()) {
            $claim?->delete();

            throw SocialAuthException::lexicon('error_verify_token');
        }

        $userId = (int) $claim->user_id;
        $email  = (string) $claim->email;

        // Re-check ownership at confirmation time, not only at claim time:
        // someone else may have proven the same address in between, and the
        // e-mail column is unique.
        $owner = Credentials::findUserByEmail($email);

        if ($owner !== null && (int) $owner->getKey() !== $userId) {
            $claim->delete();

            throw SocialAuthException::lexicon('error_email_taken');
        }

        try {
            $userModel = Config::getUserModel();
            $user      = $userModel::query()->with('attributes')->find($userId);

            if ($user === null || $user->attributes === null) {
                throw new \RuntimeException('user or profile row missing');
            }

            $user->attributes->email = $email;
            $user->attributes->save();

            // Hand the verified flag to the core's own service so that anything
            // else reading it — and the verified_key column — stays consistent.
            \UserManager::verified([
                'username'     => $user->username,
                'verified_key' => static::issueVerifiedKey($userId),
            ]);

            $claim->delete();
        } catch (\Throwable $e) {
            Log::error("Failed to confirm the e-mail for user #{$userId}: " . $e->getMessage());

            throw SocialAuthException::lexicon('error_generic');
        }

        Log::info("User #{$userId} verified their e-mail address.");

        return ['user_id' => $userId, 'email' => $email];
    }

    /**
     * The claim a user currently has outstanding, if any.
     */
    public static function pendingFor(int $userId): ?EmailVerification
    {
        try {
            $claim = EmailVerification::forUser($userId);
        } catch (\Throwable $e) {
            return null;
        }

        if ($claim === null) {
            return null;
        }

        if ($claim->hasExpired()) {
            try {
                $claim->delete();
            } catch (\Throwable $e) {
                // Nothing depends on the cleanup succeeding.
            }

            return null;
        }

        return $claim;
    }

    /**
     * Abandon an outstanding claim.
     */
    public static function cancel(int $userId): void
    {
        try {
            EmailVerification::query()->where('user_id', $userId)->delete();
        } catch (\Throwable $e) {
            Log::warning("Failed to cancel the e-mail claim for user #{$userId}: " . $e->getMessage());
        }
    }

    /**
     * Whether a user's stored address has been proven.
     */
    public static function isVerified(int $userId): bool
    {
        try {
            $userModel = Config::getUserModel();
            $user      = $userModel::query()->with('attributes')->find($userId);
        } catch (\Throwable $e) {
            return false;
        }

        $email = $user?->attributes?->email;

        if (!is_string($email) || $email === '' || UserResolver::isPlaceholderEmail($email)) {
            return false;
        }

        return (int) ($user->attributes->verified ?? 0) === 1;
    }

    /**
     * Ask the core for a fresh verified_key so that verified() will accept it.
     *
     * The core's flow issues the key and mails it; here the proof already
     * happened — the user followed a link sent to the address — so the key is
     * issued and spent in one step purely to go through the supported door
     * rather than writing `verified` by hand.
     */
    protected static function issueVerifiedKey(int $userId): string
    {
        $user = \UserManager::getVerifiedKey(['id' => $userId]);

        return (string) $user->verified_key;
    }

    protected static function sendVerificationMail(string $email, string $token, string $context): bool
    {
        $link = Config::verifyUrl($token);

        $body = '<p>' . e(__('aSocialAuth::login.verify_mail_intro')) . '</p>'
            . '<p><a href="' . e($link) . '">' . e(__('aSocialAuth::login.verify_mail_link')) . '</a></p>'
            . '<p><small>' . e(__('aSocialAuth::login.verify_mail_ignore')) . '</small></p>';

        try {
            return (bool) evo()->sendmail([
                'from'    => evo()->getConfig('site_name') . '<' . evo()->getConfig('emailsender') . '>',
                'to'      => $email,
                'subject' => __('aSocialAuth::login.verify_mail_subject'),
                'body'    => $body,
                'type'    => 'html',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to send a verification mail: ' . $e->getMessage());

            return false;
        }
    }

    protected static function generateToken(): string
    {
        return bin2hex(random_bytes(24));
    }
}
