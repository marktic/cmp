<?php

declare(strict_types=1);

namespace Marktic\Cmp\ConsentLogs\Actions;

use Marktic\Cmp\ConsentLogs\Models\ConsentLog;
use Marktic\Cmp\Consents\Enums\ConsentSource;
use Marktic\Cmp\Users\Models\User;

/**
 * Creates a single consent log record linked to a user.
 *
 * Usage:
 *
 *   $log = (new RecordConsentLog())->handle(
 *       user:    $user,
 *       request: $request,
 *       payload: $payloadArray,
 *   );
 */
class RecordConsentLog extends AbstractAction
{
    /**
     * @param User               $user     The resolved user record.
     * @param object|null        $request  Optional request object for IP/UA resolution.
     * @param array|string       $payload  The payload to store (array or JSON string).
     * @param ConsentSource      $source   Consent source (defaults to API).
     */
    public function handle(
        User $user,
        object|null $request,
        array|string $payload,
        ConsentSource $source = ConsentSource::API,
    ): ConsentLog {
        $repository = $this->getRepository();

        $encodedPayload = is_array($payload)
            ? json_encode($payload, JSON_THROW_ON_ERROR)
            : $payload;

        [$ipAddress, $userAgent] = $this->resolveRequestMeta($request);

        /** @var ConsentLog $log */
        $log = $repository->getNewRecord([
            'user_id'    => intval($user->id),
            'session_id' => $user->getSessionId() ?? '',
            'payload'    => $encodedPayload,
            'source'     => $source->value,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        $repository->save($log);

        return $log;
    }

    /**
     * Extract IP address and User-Agent from a request object using common
     * framework conventions (e.g. Symfony HttpFoundation).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveRequestMeta(object|null $request): array
    {
        if ($request === null) {
            return [null, null];
        }

        $ipAddress = null;
        $userAgent = null;

        if (method_exists($request, 'getClientIp')) {
            $ipAddress = $request->getClientIp();
        }

        if (isset($request->headers) && method_exists($request->headers, 'get')) {
            $userAgent = $request->headers->get('User-Agent');
        } elseif (method_exists($request, 'header')) {
            $userAgent = $request->header('User-Agent');
        }

        return [
            $ipAddress !== '' ? $ipAddress : null,
            $userAgent !== '' ? $userAgent : null,
        ];
    }
}
