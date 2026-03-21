<?php

use Hashids\Hashids;

if (!function_exists('encode')) {
    /**
     * Codifica un ID usando Hashids.
     */
    function encode(int $codigo)
    {
        $hashids = new Hashids(config('app.key'), 10);

        return $hashids->encode($codigo);
    }
}

if (!function_exists('decode')) {
    /**
     * Decodifica un ID usando Hashids.
     */
    function decode(?string $hash)
    {
        if (!$hash) { return null; }

        $hashids = new Hashids(config('app.key'), 10);

        $decoded = $hashids->decode($hash);

        return !empty($decoded) ? $decoded[0] : null;
    }
}