<?php

declare(strict_types=1);

namespace M2E\OnBuy\Setup;

class UpgradeCollection extends \M2E\Core\Model\Setup\AbstractUpgradeCollection
{
    public function getMinAllowedVersion(): string
    {
        return '1.0.0';
    }

    protected function getSourceVersionUpgrades(): array
    {
        return [
            '1.0.0' => ['to' => '1.0.1', 'upgrade' => null],
            '1.0.1' => ['to' => '1.1.0', 'upgrade' => \M2E\OnBuy\Setup\Upgrade\v1_1_0\Config::class],
            '1.1.0' => ['to' => '1.1.1', 'upgrade' => null],
            '1.1.1' => ['to' => '1.1.2', 'upgrade' => \M2E\OnBuy\Setup\Upgrade\v1_1_2\Config::class],
            '1.1.2' => ['to' => '1.2.0', 'upgrade' => \M2E\OnBuy\Setup\Upgrade\v1_2_0\Config::class],
            '1.2.0' => ['to' => '1.2.1', 'upgrade' => null],
            '1.2.1' => ['to' => '1.3.0', 'upgrade' => \M2E\OnBuy\Setup\Upgrade\v1_3_0\Config::class],
            '1.3.0' => ['to' => '1.3.1', 'upgrade' => null],
            '1.3.1' => ['to' => '1.4.0', 'upgrade' => null],
            '1.4.0' => ['to' => '1.5.0', 'upgrade' => \M2E\OnBuy\Setup\Upgrade\v1_5_0\Config::class],
            '1.5.0' => ['to' => '1.6.0', 'upgrade' => \M2E\OnBuy\Setup\Upgrade\v1_6_0\Config::class],
            '1.6.0' => ['to' => '1.6.1', 'upgrade' => null],
            '1.6.1' => ['to' => '1.6.2', 'upgrade' => null],
        ];
    }
}
