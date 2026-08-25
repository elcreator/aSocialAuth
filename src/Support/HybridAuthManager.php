<?php

namespace Elcreator\aSocialAuth\Support;

use Elcreator\aSocialAuth\Models\RegisteredProvider;
use Hybridauth\Adapter\AdapterInterface;

/**
 * Builds a configured HybridAuth adapter for a registered provider.
 *
 * The adapter is assembled from three sources, in order of increasing
 * specificity: HybridAuth's own defaults, the catalogue (which knows the scope a
 * provider needs before it will return an e-mail), and the site's config (which
 * can override anything). Nothing about a particular network is special-cased
 * here — that is what lets all 50-odd HybridAuth providers work without a line
 * of code each.
 */
class HybridAuthManager
{
    /**
     * @param RegisteredProvider $provider a provider from the registry
     * @throws \RuntimeException when the adapter class cannot be loaded
     */
    public static function adapterFor(RegisteredProvider $provider): AdapterInterface
    {
        $class = $provider->adapterClass();

        if (!class_exists($class)) {
            throw new \RuntimeException(
                "HybridAuth adapter '{$class}' for provider '{$provider->slug}' was not found."
            );
        }

        $adapter = new $class(static::adapterConfig($provider));

        if (!$adapter instanceof AdapterInterface) {
            throw new \RuntimeException(
                "Adapter '{$class}' for provider '{$provider->slug}' does not implement HybridAuth's AdapterInterface."
            );
        }

        return $adapter;
    }

    /**
     * Resolve a slug and build its adapter in one step.
     *
     * @throws \RuntimeException when the slug is unknown or not on offer
     */
    public static function getAdapter(string $providerSlug): AdapterInterface
    {
        $provider = ProviderRegistry::resolve($providerSlug);

        if ($provider === null) {
            throw new \RuntimeException("Social provider '{$providerSlug}' is not enabled or not configured.");
        }

        return static::adapterFor($provider);
    }

    /**
     * The config array handed to the HybridAuth adapter constructor.
     */
    public static function adapterConfig(RegisteredProvider $provider): array
    {
        $slug   = $provider->slug;
        $entry  = ProviderRegistry::configFor($slug);
        $case   = $provider->catalogue();

        $config = [
            'callback' => Config::buildCallbackUrl($slug),
            'keys'     => ProviderRegistry::credentials($slug),
        ];

        // The catalogue's scope is the one the provider needs before it will
        // hand back a usable profile; the site may still override it.
        $scope = $entry['scope'] ?? $case?->defaultScope();

        if (is_string($scope) && $scope !== '') {
            $config['scope'] = $scope;
        }

        if (!empty($entry['extra']) && is_array($entry['extra'])) {
            $config = array_merge($config, $entry['extra']);
        }

        return $config;
    }
}
