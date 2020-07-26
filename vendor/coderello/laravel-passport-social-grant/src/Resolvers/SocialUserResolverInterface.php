<?php

namespace Coderello\SocialGrant\Resolvers;

use Illuminate\Contracts\Auth\Authenticatable;

interface SocialUserResolverInterface
{
    /**
     * Resolve user by provider credentials.
     *
     * @param string $provider
     * @param string $accessToken
     * @param string $secret
     *
     * @return Authenticatable|null
     */
    public function resolveUserByProviderCredentials(string $provider, string $accessToken, string $secret = null): ?Authenticatable;
}
