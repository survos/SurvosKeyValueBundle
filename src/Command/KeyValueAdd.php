<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Command;

use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('survos:key-value:add', 'Add data to key/value storage', aliases: ['kv:add'])]
class KeyValueAdd extends BaseKeyValue
{
    protected function configure(): void
    {
        $this
            ->addArgument('value', InputArgument::REQUIRED, 'Value to be blocked')
            ;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $this->kvManager->add(
            $value = $this->getValue($input),
            $type  = $this->getType($input)
        );
        (new SymfonyStyle($input, $output))->success("Added $type $value");
        return self::SUCCESS;
    }
}
