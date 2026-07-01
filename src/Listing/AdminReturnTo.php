<?php

namespace Ubermuda\FeatureFlagsBundle\Listing;

use Psr\Log\LoggerInterface;

/**
 * Validates an opaque `returnTo` URL coming back from a list-page Edit/Delete/Toggle
 * round trip, so saving or acting on a row returns to the same filtered list.
 *
 * Only same-site local paths are accepted (a single leading slash, no scheme and no
 * protocol-relative `//`), defending against open-redirect probes. A rejected non-null
 * candidate is logged at info level so suspicious payloads / buggy callers are observable.
 */
final readonly class AdminReturnTo
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Returns the candidate when it is a valid local path, else null. Accepts `mixed`
     * so callers can pass `$request->query->get('returnTo')` directly.
     */
    public function validate(mixed $candidate): ?string
    {
        if (!is_string($candidate) || '' === $candidate) {
            return null;
        }

        $isLocalPath = str_starts_with($candidate, '/')
            && !str_starts_with($candidate, '//')
            && !str_contains($candidate, '\\');

        if (!$isLocalPath) {
            $this->logger->info('feature_flags.return_to_rejected', [
                'return_to_prefix' => substr($candidate, 0, 32),
            ]);

            return null;
        }

        return $candidate;
    }
}
