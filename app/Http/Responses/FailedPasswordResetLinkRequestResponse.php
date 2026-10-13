<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse as BaseFailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reports an unknown email as a sent link so the form cannot be used to discover accounts.
 */
class FailedPasswordResetLinkRequestResponse extends BaseFailedPasswordResetLinkRequestResponse
{
    public function toResponse($request): Response
    {
        if ($this->status === Password::INVALID_USER) {
            return new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT)->toResponse($request);
        }

        return parent::toResponse($request);
    }
}
