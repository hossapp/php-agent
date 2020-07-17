<?php

require __DIR__ . '/../../src/Hoss/Agent.php';

use Hoss\Agent;

Agent::instrument('staging-Ad7N9vyP3ADAJ8VCVM3eHw7c2XuoJNujBEKe5gPLxWVd');

require __DIR__.'/file2.php';

makeRequest();
?>
