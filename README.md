# Key Value Bundle

A wrapper for https://gist.github.com/sbrl/c3bfbbbb3d1419332e9ece1bac8bb71c

## Usage

```bash
composer require survos/key-value-bundle
```

```php
$kvDb = new StorageBox('lookup.db');
$kvDb->set('dog', 'perro');
$trans = $kvDb->get('dog');
// trans is perro
$trans = $kvDb->get('cat');
// trans is null

// cache-like with callcack
$trans = $kvDb->get('dog', fn($item) => $this->translator->trans('dog'));

// keys are automatically slugified
$trans = $kvDb->get('My name is', fn($item) => $this->translator->trans($item->key));

// keys are automatically slugified
$trans = $kvDb->get('My name is',   ) => $this->translator->trans($item->key));

```
