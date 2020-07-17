<?php

require __DIR__ . '/../../src/Hoss/Agent.php';

use Hoss\Agent;

Agent::instrument('HOSS API KEY');

require __DIR__.'/file2.php';

makeRequest();
?>
