# Key Value Bundle

A wrapper for https://gist.github.com/sbrl/c3bfbbbb3d1419332e9ece1bac8bb71c

## Usage

```bash
composer require survos/key-value-bundle
```

Initialize StorageBox with the sqlite database name plus any new tables to be created.

Existing tables can be used, but automatic table creation is disabled. 

```php
$kvDb = new StorageBox('translation.db', ['es', 'en'], 'en');
$key = md5('dog');
$kvDb->set($key, 'perro', 'es');
$kvDb->set($key, 'dog'); // defaults to en
$trans = $kvDb->get($key); // dog 
$trans = $kvDb->get($key, 'es'); // perro 
// trans is perro

// cache-like with callcack
$trans = $kvDb->get($key, callback: fn($item) => $this->translator->trans('dog'));

// keys are automatically slugified
$trans = $kvDb->get('My name is', fn($item) => $this->translator->trans($item->key));

// keys are automatically slugified
$trans = $kvDb->get('My name is',   ) => $this->translator->trans($item->key));

```


## Multi-lingual StorageBox

Specifically for kv lookups of text fields that may have translations

```php
$kvDb = new MLSB('property.db', ['label','description']);

$key = 'Q31';
$kvDb->set($key, 'label', 'Belgium', 'en-gb');
$kvDb->set($key, 'description', 'constitutional monarchy in Western Europe', 'en-gb');

$transArray = $kvDb->getFieldsByLocale($key, 'en-gb'); 
// ['label' => 'Belgium'...]

$label = $kvDb->getField($key, 'label', 'en-gb'); 
// 'Belgium'

$transArray = $kvDb->getFields($key); 
// ['en-gb' => ['label' => 'Belgium', 'description'  => ...]]

// ditto for set, needs transactions


