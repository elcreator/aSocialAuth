<?php

namespace Elcreator\aSocialAuth\Console;

use Elcreator\aSocialAuth\Support\Packages;
use Illuminate\Console\Command;

/**
 * Lists the features that need a package of their own; `--install` installs the
 * missing ones through the CMS's `package:installrequire`.
 */
class PackagesCommand extends Command
{
    protected $signature = 'asocialauth:packages
        {--install=* : Install these features\' packages (ids, repeated or comma-separated, e.g. onetap)}';

    protected $description = 'List optional aSocialAuth features and whether their packages are installed, or install them';

    public function handle(): int
    {
        $wanted = $this->wanted();

        return $wanted === [] ? $this->list() : $this->install($wanted);
    }

    protected function list(): int
    {
        $rows = [];

        foreach (Packages::features() as $feature) {
            $available = Packages::available($feature);

            $rows[] = [
                $feature,
                Packages::label($feature),
                $available ? 'installed' : 'missing',
                $available ? '' : Packages::installCommand($feature),
            ];
        }

        $this->table(['Id', 'Feature', 'Status', 'Install'], $rows);

        return self::SUCCESS;
    }

    /** @param string[] $features */
    protected function install(array $features): int
    {
        $unknown = array_values(array_filter($features, static fn (string $id): bool => !Packages::has($id)));

        if ($unknown !== []) {
            $this->error('Unknown feature: ' . implode(', ', $unknown) . '. Run asocialauth:packages for the list.');

            return self::FAILURE;
        }

        foreach ($features as $feature) {
            if (Packages::available($feature)) {
                $this->line(Packages::label($feature) . ' is already installed.');
                continue;
            }

            // One Composer run per package: package:installrequire rolls back only its own.
            foreach (Packages::missing($feature) as $package) {
                $this->info('Installing ' . $package . '...');

                if ($this->installPackage($package) !== 0) {
                    $this->error('Installing ' . $package . ' failed; the site keeps its previous packages.');

                    return self::FAILURE;
                }
            }
        }

        return self::SUCCESS;
    }

    /** @return string[] feature ids from every --install value */
    protected function wanted(): array
    {
        $ids = [];

        foreach ((array) $this->option('install') as $value) {
            foreach (explode(',', (string) $value) as $id) {
                if (trim($id) !== '') {
                    $ids[] = trim($id);
                }
            }
        }

        return array_values(array_unique($ids));
    }

    protected function installPackage(string $package): int
    {
        return (int) $this->call('package:installrequire', ['key' => $package, 'value' => '*']);
    }
}
