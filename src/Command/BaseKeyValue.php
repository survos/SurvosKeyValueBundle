<?php declare(strict_types=1);

namespace Survos\KeyValueBundle\Command;

use Survos\KeyValueBundle\Entity\KeyValue;
use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseKeyValue extends Command
{
    public function __construct(protected readonly KeyValueManagerInterface $kvManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'KeyValue type, e.g. "email"');
    }

    public function getValue(InputInterface $input): ?string
    {
        /** @var string */
        $value = $input->getArgument('value');
        return $value;
    }

    public function getType(InputInterface $input): ?string
    {
        /** @var string */
        $type = $input->getArgument('type');
        $type ??= $this->kvManager->getDefaultList();
        return $type;
    }


}
