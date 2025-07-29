<?php

namespace App\Support;

use Illuminate\Support\Collection;

class Config
{
    public function __construct(protected array $config)
    {
        //
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    public function remove(string $key): void
    {
        unset($this->config[$key]);
    }

    public function toArray(): array
    {
        return $this->config;
    }

    public function getId(): string
    {
        return $this->get('id');
    }

    public function getBaseUrl(): string
    {
        return $this->get('base_url');
    }

    public function getPrinters(): Collection
    {
        return Collection::make($this->get('printers', []));
    }

    public function getPrinterCredentials(array $printer): array
    {
        $defaultCredentials = $this->get('default_credentials');

        return [
            $printer['username'] ?? $defaultCredentials['username'] ?? null,
            $printer['password'] ?? $defaultCredentials['password'] ?? null,
        ];
    }
}
