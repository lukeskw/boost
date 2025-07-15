<?php

declare(strict_types=1);

namespace Laravel\Boost\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

trait MakesHttpRequests
{
    public function client(): PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:140.0) Gecko/20100101 Firefox/140.0 Laravel Boost',
        ]);
    }

    public function get(string $url): Response
    {
        return $this->client()->get($url);
    }
}
