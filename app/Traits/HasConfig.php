<?php

namespace App\Traits;

use App\Support\Config;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * @mixin Command
 */
trait HasConfig
{
    protected function getYamlFilename(): string
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE');
        if (!$home && function_exists('posix_getpwuid')) {
            $home = posix_getpwuid(posix_getuid())['dir'] ?? null;
        }

        if (!$home) {
            throw new \RuntimeException("Unable to determine user's home directory.");
        }

        return sprintf('%s/print-cli.yml', $home);
    }

    protected function getConfig($expectedVersion = null): Config
    {
        try {
            $yaml = file_get_contents($this->getYamlFilename());
        } catch (Throwable) {
            throw new RuntimeException(sprintf(
                'Unable to read configuration file %s',
                $this->getYamlFilename(),
            ));
        }

        $config = Yaml::parse($yaml);

        if ($expectedVersion !== null) {
            if (!isset($config['version'])) {
                throw new RuntimeException('Configuration file does not have a version.');
            }

            if ($config['version'] !== $expectedVersion) {
                throw new RuntimeException(sprintf(
                    'Configuration file version %s does not match expected version %s.',
                    $config['version'],
                    $expectedVersion
                ));
            }
        }

        if (!isset($config['base_url'])) {
            throw new RuntimeException('Configuration file does not have a base_url.');
        }

        return new Config($config);
    }

    protected function setConfig(array $config): void
    {
        $yaml = Yaml::dump(
            input: $config,
            inline: 100,
            indent: 2,
            flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
        );

        file_put_contents(
            filename: $this->getYamlFilename(),
            data: preg_replace('/-\n\s+/', '- ', $yaml)
        );
    }
}
