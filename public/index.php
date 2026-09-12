<?php

declare(strict_types=1);

$app = require __DIR__ . '/../src/bootstrap.php';

require __DIR__ . '/../routes/api.php';

$app['router']->dispatch($app['request']);
