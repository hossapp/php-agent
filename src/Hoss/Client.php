<?php

namespace Hoss;

require_once(__DIR__.'/Consumers/LibCurl.php');
require_once(__DIR__.'/Consumers/Socket.php');

use Hoss\Configuration;
use Hoss\Consumers\LibCurlConsumer;
use Hoss\Consumers\SocketConsumer;

class Client
{
  protected $consumer;
  public $configuration;
  public function __construct($apiKey,  $configuration)
  {
      # default consumer. Should be customizable by configuration
      $this->consumer = new LibCurlConsumer($apiKey, $configuration);
      $this->configuration = $configuration;
  }

  public function __destruct()
  {
      $this->consumer->__destruct();
  }

    public function queue(Event $event)
  {
      $this->consumer->queue($event);
  }
}

?>
