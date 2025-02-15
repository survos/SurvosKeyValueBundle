# KeyValueBundle

Flexible bundle to handle Key Value(s) list, e.g. a dynamic list of ips and paths to block bad bots.

Highly inspired by  lsbproject/blacklist-bundle https://github.com/AntoineLemaire/BlacklistBundle

Installation
============

```console
composer require survos/key-value-bundle
```

### Update database schema

```console
bin/console doctrine:schema:update --force
```

## Purpose

I found myself needing short, configurable lists in different application -- translation memory, spelling checks and most commonly, looking for paths and path patterns to include/exclude during monitoring.  

## Usage

Add properties by key, which are repeatable.

```console
bin/console survos:kv:add excluded_password password
bin/console survos:kv:add excluded_password admin root 123
```

Then in code, check

```php
    #[Route('/', name: 'app_homepage', methods: [Request::METHOD_GET])]
    public function index(
                          KeyValueManagerInterface $kvManager,
                          ): Response
    {
        if ($kvManager->has($password, 'excluded_password')) {
            // 
        }
    }
```

This was originally design for use with BlockBotBundle

```bash
bin/console survos:kv:add bad_bot_path_pattern "wp-admin"
bin/console survos:kv:add bad_bot_path_pattern "phpinfo.php"
bin/console survos:kv:add bad_bot_path_pattern "\.php^"
```

```php
    #[AsEventListener(RequestEvent::class, priority: 10000)]
    public function onKernelRequest(RequestEvent $event): void
    {
        foreach ($this->kvManager->get('bad_bot_path_pattern') as $pattern) {
            if (preg_match("$pattern", $path)) {
                // temporarily block this IP 
            }
        }
```


