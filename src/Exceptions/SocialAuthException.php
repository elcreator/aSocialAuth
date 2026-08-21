<?php

namespace EvolutionCMS\aSocialAuth\Exceptions;

/**
 * A social login that failed for a reason the visitor can be told about.
 *
 * The message is already localised and safe to show: it is chosen from the
 * package's language file rather than built from provider output, so an error
 * page never echoes back something a third party controls. Anything unexpected
 * stays a plain \Throwable, gets logged, and is reported to the visitor as a
 * generic failure.
 */
class SocialAuthException extends \RuntimeException
{
    /**
     * @param string $lexicon key under the aSocialAuth::login language file
     */
    public static function lexicon(string $lexicon, array $replace = []): self
    {
        $message = __('aSocialAuth::login.' . $lexicon, $replace);

        // __() returns the key itself when the entry is missing; that is not a
        // sentence to show anyone, so fall back to the generic message.
        if (!is_string($message) || str_starts_with($message, 'aSocialAuth::')) {
            $message = __('aSocialAuth::login.error_generic');
        }

        return new self(is_string($message) ? $message : 'Social login failed.');
    }
}
