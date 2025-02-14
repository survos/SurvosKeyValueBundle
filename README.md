# BadBotBundle

Flexible bundle to handle Key Value(s) list, e.g. a dynamic list of ips and paths to block bad bots.

Highly inspired by  lsbproject/key-value-bundle https://github.com/AntoineLemaire/KeyValueBundle

Installation
============

```console
composer require survos/bad-bot-bundle
```

### Update database schema

```console
bin/console doctrine:schema:update --force
bin/console bot:populate https://github.com/mitchellkrogza/apache-ultimate-bad-bot-blocker/raw/refs/heads/master/_generator_lists/bad-ip-addresses.list
```

Usage
=====

@todo: https://github.com/mitchellkrogza/apache-ultimate-bad-bot-blocker


@todo: refactor with annotations.

Really this part could be a generic KeyValuesBundle, e.g. $kvManager->list('ip')

```php
    use Survos\KeyValueBundle\Validator\Constraints\IsNotKeyValueed;

    //...

    /**
     * 'baz' type isn't defined in bundle, so it will be handled with
     * default_type class. Default one has no validation and will compare
     * any value with other existed
     *
     * @IsNotKeyValueed(type="baz", caseSensetive=true)
     * @var string
     */
    private $bar;

    /**
     * 'email' type will dissallow to put invalid emails in key-value
     *
     * @IsNotKeyValueed(type="email", caseSensetive=true)
     * @var string
     */
    private $email;
```

Types
-----

Bundle tries to validate exact key-value type with validator types.
You can implement your own type or use default one.
To add your own validator just implement `TypeInterface`

e.g.

```php
use Survos\KeyValueBundle\Type\TypeInterface;
use Survos\KeyValueBundle\Type\DefaultType;

class EmailType extends DefaultType implements TypeInterface
{
    /**
     * {@inheritDoc}
     */
    public function satisfies(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'email';
    }
}
```

and tag it with `lsbproject.key-value.type`

```yaml
  email_key-value_type:
    class: 'Survos\KeyValueBundle\Type\EmailType'
    tags:
      - { name: 'lsbproject.key-value.type' }
```

Default
-------

If there are no supported types found bundle will use default type.
You can override it in config:

```yaml
    lsb_project_key-value:
      default_type: Survos\KeyValueBundle\Type\DefaultType
```

Validate storage
----------------

If you do not want to use database as a storage for key-value you
can implement your own `validate` method for a separate or default types.

example of default `validate`

```php
class DefaultType implements TypeInterface
{
    //...    

    /**
     * {@inheritDoc}
     */
    public function validate(
        string $value,
        Constraint $constraint,
        ExecutionContextInterface &$context,
        KeyValueManagerInterface $manager
    ): void {
        if ($manager->isKeyValueed($value, $constraint->type, $constraint->caseSensetive)) {
            $context->buildViolation($constraint->message)->addViolation();
        }
    }
}
```
