<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Command;

use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('survos:key-value:remove', 'Remove data from key/value storage', aliases: ['kv:remove'])]
class KeyValueRemove extends BaseKeyValue
{
    protected function configure(): void
    {
        $this
            ->addArgument('value', InputArgument::REQUIRED, 'Value to be removed')
            ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $value = $this->getValue($input);
            $type  = $this->getType($input);
        if (!$this->kvManager->has($value, $type)) {
            $style->warning('Key value "' . $type . '/' .  $value . '" does not exists.');
            return self::SUCCESS;
        }

        $this->kvManager->remove(value: $value, type: $type);
        $style->success("deleted $type $value");
        return self::SUCCESS;
    }
}
