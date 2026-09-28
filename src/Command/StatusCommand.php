<?php

namespace Wexample\SymfonyRemote\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wexample\SymfonyHelpers\Command\AbstractBundleCommand;
use Wexample\SymfonyHelpers\Service\BundleService;
use Wexample\SymfonyRemote\Class\RemoteStatus;
use Wexample\SymfonyRemote\Enum\RemoteState;
use Wexample\SymfonyRemote\Service\RemoteRegistry;
use Wexample\SymfonyRemote\WexampleSymfonyRemoteBundle;

/**
 * Checks the app's remotes. Fails when one is Down, so a cron or a probe can
 * run it as is; Unconfigured does not fail, being a setup matter, not an outage.
 */
class StatusCommand extends AbstractBundleCommand
{
    public const string FORMAT_JSON = 'json';

    public const string FORMAT_TABLE = 'table';

    public function __construct(
        BundleService $bundleService,
        private readonly RemoteRegistry $registry,
    ) {
        parent::__construct($bundleService);
    }

    public static function getBundleClassName(): string
    {
        return WexampleSymfonyRemoteBundle::class;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Checks whether the app\'s remotes answer.')
            ->addArgument('key', InputArgument::OPTIONAL, 'Check this remote only.')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'table or json', self::FORMAT_TABLE);
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $key = $input->getArgument('key');
        $statuses = null !== $key
            ? [$key => $this->registry->check($key)]
            : $this->registry->checkAll();

        if (self::FORMAT_JSON === $input->getOption('format')) {
            $output->writeln(json_encode(
                array_map($this->toArray(...), $statuses, array_keys($statuses)),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            ));
        } else {
            (new SymfonyStyle($input, $output))->table(
                ['Remote', 'Label', 'State', 'Latency (ms)', 'Message'],
                array_map(fn (RemoteStatus $status, string $key): array => [
                    $key,
                    $this->registry->get($key)->getLabel(),
                    $status->state->value,
                    null === $status->latency ? '' : number_format($status->latency, 1),
                    $status->message ?? '',
                ], $statuses, array_keys($statuses))
            );
        }

        foreach ($statuses as $status) {
            if (RemoteState::Down === $status->state) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(
        RemoteStatus $status,
        string $key
    ): array {
        return [
            'key' => $key,
            'label' => $this->registry->get($key)->getLabel(),
            'state' => $status->state->value,
            'latency' => $status->latency,
            'message' => $status->message,
            'checkedAt' => $status->checkedAt->format(DATE_ATOM),
        ];
    }
}
