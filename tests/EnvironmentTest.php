<?php

/*
 * This file is part of composer/xdebug-handler.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\XdebugHandler\Tests;

use Composer\XdebugHandler\Tests\Helpers\BaseTestCase;
use Composer\XdebugHandler\Tests\Helpers\EnvHelper;
use Composer\XdebugHandler\Tests\Mocks\PartialMock;

/**
 * We use PHP_BINARY which only became available in PHP 5.4
 *
 * @requires PHP 5.4
 * @phpstan-import-type envTestData from EnvHelper
 */
class EnvironmentTest extends BaseTestCase
{
    /**
     * Tests that the _ALLOW_XDEBUG environment variable is correctly formatted
     * for use in the restarted process.
     *
     * @param string $iniFunc IniHelper method to use
     * @param false|string $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param false|string $phprc Initial value for PHPRC
     *
     * @dataProvider envAllowBeforeProvider
     *
     * @return void
     */
    public function testEnvAllowBeforeRestart($iniFunc, $scanDir, $phprc)
    {
        if (($message = EnvHelper::shouldSkipTest($scanDir)) !== null) {
            self::markTestSkipped($message);
        }

        $ini = EnvHelper::setInis($iniFunc, $scanDir, $phprc);
        $loaded = true;

        PartialMock::createAndCheck($loaded);

        $args = array(
            PartialMock::RESTART_ID,
            PartialMock::TEST_VERSION,
            $ini->hasScannedInis() ? '1' : '0',
            false !== $scanDir ? $scanDir : '*',
            false !== $phprc ? $phprc : '*',
        );

        $expected = implode('|', $args);
        self::assertSame($expected, getenv(PartialMock::ALLOW_XDEBUG));
    }

    /**
     * @return array
     * @phpstan-return envTestData
     */
    public function envAllowBeforeProvider()
    {
        return EnvHelper::dataProvider();
    }

    /**
     * Tests that environment variables are correctly set for a restart.
     *
     * @param string $iniFunc IniHelper method to use
     * @param false|string $scanDir Initial value for PHP_INI_SCAN_DIR
     * @param false|string $phprc Initial value for PHPRC
     * @param bool $standard If this is a standard restart
     *
     * @dataProvider environmentProvider
     *
     * @return void
     */
    public function testEnvironmentBeforeRestart($iniFunc, $scanDir, $phprc, $standard)
    {
        if (($message = EnvHelper::shouldSkipTest($scanDir)) !== null) {
            self::markTestSkipped($message);
        }

        $ini = EnvHelper::setInis($iniFunc, $scanDir, $phprc);
        $loaded = true;

        $settings = $standard ? array() : array('setPersistent' => array());

        $xdebug = PartialMock::createAndCheck($loaded, null, $settings);

        if (!$standard) {
            $scanDir = '';
            $phprc = $xdebug->getTmpIni();
        }

        $strategy = $standard ? 'standard' : 'persistent';
        self::assertSame($scanDir, getenv('PHP_INI_SCAN_DIR'), $strategy.' scanDir');
        self::assertSame($phprc, getenv('PHPRC'), $strategy.' phprc');
    }

    /**
     * @return array
     * @phpstan-return array<string, array{0: string, 1: false|string, 2: false|string, 3: bool}>
     */
    public function environmentProvider()
    {
        // $iniFunc, $scanDir, $phprc, $standard (added below)
        $data = EnvHelper::dataProvider();
        $result = array();

        foreach ($data as $test => $params) {
            $params[3] = true;
            $result[$test.' standard'] = $params;
            $params[3] = false;
            $result[$test.' persistent'] = $params;
        }

        return $result;
    }
}
