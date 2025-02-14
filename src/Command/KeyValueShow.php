<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Command;

use Survos\KeyValueBundle\Entity\KeyValue;
use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('survos:key-value:show', 'List key-valueed entities')]
class KeyValueShow extends Command
{
    public function __construct(private readonly KeyValueManagerInterface $kvManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::OPTIONAL, 'KeyValue type, e.g. "email"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string|null $type */
        $type = $input->getArgument('type');

        $style = new SymfonyStyle($input, $output);

        $list = $this->kvManager->getList($type);

        if (!$list) {
            $style->success("No entries found");

        }

        $style->table(['type', 'value'], array_map(fn(KeyValue $kv) => [$kv->getType(), $kv->getValue()], $list));

        return self::SUCCESS;
    }
}
