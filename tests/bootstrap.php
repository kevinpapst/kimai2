<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

/*
 * This file is part of the Kimai cloud environment.
 *
 * Copyright 2019 Kevin Papst. This is proprietary software.
 */

if (isset($_ENV['BOOTSTRAP_RESET_DATABASE']) && $_ENV['BOOTSTRAP_RESET_DATABASE'] == true) {
    echo 'Installing test database ...' . PHP_EOL;

    exec(sprintf(
        'APP_ENV=test php "%s/../bin/console" kimai:reset-test --env=test --no-interaction -vvv',
        __DIR__
    ), $output, $exitCode);

    if ($exitCode !== 0) {
        dump($output);
        throw new \Exception('Failed to setup test database.');
    }
}
