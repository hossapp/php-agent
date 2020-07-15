# Hoss Node Agent

This is the php agent for Hoss Agent

Hoss helps you track and manage API integrations. It automatically capture outbound requests and alert API become slow, return error or unexpected data. Sign up for a free account at https://www.hoss.com


## Prerequisites

- Hoss account and API Key
- PHP 7.4 or higher

## Getting started

* If you're using composer, run

```shell script
$ composer require hoss/php-agent
```

* Add the following to your PHP script to load the library.

```php
require_once("/path/to/library/src/Hoss/Agent.php");

Hoss\Agent::instrument(<Hoss API KEY>);

```

## Supported technologies

We support automatic instrumentation for the following http client
* cURL

If you need more fine grained control over how and when to perform instrumentation, the agent also supports manual instrumentation. See below for details

## Options
The agent accepts an array of configuration as well as environment variable

| Option                       | Environment variable              | Type    | Description                                                                                                               | Default value            |
|------------------------------|-----------------------------------|---------|---------------------------------------------------------------------------------------------------------------------------|--------------------------|
| ingress_host                 | HOSS_INGRESS_HOST                 | string  | Hoss ingress server                                                                                                       | https://ingress.hoss.com |
| api_host                     | HOSS_API_HOST                     | string  | Hoss api host                                                                                                             | https://app.hoss.com     |
| debug                        | HOSS_DEBUG                        | boolean | If true, debug log is written                                                                                             | false                    |
| max_queue_size               | HOSS_MAX_QUEUE_SIZE               | number  | The maximum number of items in the queue. Once the queue is full, early items are dropped                                 | 1000                     |
| batch_size                   | HOSS_BATCH_SIZE                   | number  | Number of requests to queue up before sending                                                                             | 20                       |
| queue_consumer               | HOSS_QUEUE_CONSUMER               | String  | Either LibCurl or Socket depending on the performance requirement of your application. See queue consumer for more detail | LibCurl                  |
| max_backoff_duration         | HOSS_MAX_BACKOFF_DURATION         | number  | How long (in ms) the agent will wait for ingress to return a response. Only valid if queue_consumer is LibCurl.           | 1000                     |
| remote_config_enabled        | HOSS_REMOTE_CONFIG_ENABLED        | boolean | If true, you can control the agent capture settings using Hoss application                                                | true                     |
| remote_config_fetch_interval | HOSS_REMOTE_CONFIG_FETCH_INTERVAL | number  | How long (in s) will the agent cache its remote configuration. Only valid if remote_config_enabled is true                | 300                      |

## Capture options
You can control many aspect of how the agent capture requests like field sanitization, blocked hosts, etc... 

Unlike the above options, you can update these from Hoss application without a deploy. The agent will periodically query for config changes and update itself. 

You can control these settings with code. Please note that these will apply to all APIs. If you want to apply only to specific APIs, please use remote configuration options.

| Option                 | Environment variable       | Type            | Description                                                                                                                                                                                                                          | Default value |
|------------------------|----------------------------|-----------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|---------------|
| body_capture           | HOSS_BODY_CAPTURE          | string          | One of "On", "Off" and "OnError". Control if the agent will include the request and response body in the event. If OnError is selected, the payload is only captured for request with 400 and above status code and connection error | On            |
| blocked_hosts          | HOSS_BLOCK_HOSTS           | array of string | The agent will not capture requests to any of these hosts and its subdomain                                                                                                                                                          | array()       |
| sanitized_headers      | HOSS_SANITIZED_HEADERS     | array of string | The agent will not capture values of these headers                                                                                                                                                                                   | array()       |
| sanitized_query_fields | HOSS_SANITIZED_QUERY       | array of string | The agent will not capture these query params                                                                                                                                                                                        | array()       |
| sanitized_body_fields  | HOSS_SANITIZED_BODY_FIELDS | array of string | For JSON request & response only. The agent will not capture fields whose key are in this list                                                                                                                                       | array()       |

## Advanced topis

### Queue consumers
Because PHP is a single threaded and shared-nothing environment, we can't use a queue in a separate thread or a connection pool to flush messages. You have the option to specify different consumers to make requests to our servers

#### Lib curl consumer
The lib curl consumer is ideal for low-volume application. The consumer run synchronously, queuing calls and sending them in batches to Hoss' servers. By default, this happens every 20 messages or at the end of serving a request.

#### Socket consumer
The socket consumer will open a socket connection to our server. Then the batch of message is ready, it's written to the socket and immediately close the connection without waiting for a response. If you cannot use a persistent connection, you may want to use one of the other consumers instead

#### File consumer
Coming soon

  