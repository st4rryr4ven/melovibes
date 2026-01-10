<?php

namespace App\Command;

use App\Service\Spotify\SpotifyCatalogService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:spotify:sync:new-releases',
    description: 'Synchronize Spotify new releases into the local database.'
)]
class SpotifySyncNewReleasesCommand extends Command
{
    public function __construct(
        private readonly SpotifyCatalogService $catalog,
        #[Autowire('%env(default:FR:SPOTIFY_NEW_RELEASES_COUNTRY)%')] private readonly string $defaultCountry = 'FR',
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('country', null, InputOption::VALUE_REQUIRED, 'Country (ISO 3166-1 alpha-2)', $this->defaultCountry)
            ->addOption('album-limit', null, InputOption::VALUE_REQUIRED, 'Album page size (max 50)', 20)
            ->addOption('album-offset', null, InputOption::VALUE_REQUIRED, 'Album offset', 0)
            ->addOption('max-tracks-per-album', null, InputOption::VALUE_REQUIRED, 'Max tracks imported per album', 50)
            ->addOption('update-existing', null, InputOption::VALUE_NEGATABLE, 'Update existing rows matched by link', true)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run without writing to the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $country = (string) $input->getOption('country');
        $albumLimit = (int) $input->getOption('album-limit');
        $albumOffset = (int) $input->getOption('album-offset');
        $maxTracksPerAlbum = (int) $input->getOption('max-tracks-per-album');
        $updateExisting = (bool) $input->getOption('update-existing');
        $dryRun = (bool) $input->getOption('dry-run');

        $count = $this->catalog->syncNewReleases(
            $country,
            $albumLimit,
            $albumOffset,
            $maxTracksPerAlbum,
            $updateExisting,
            $dryRun
        );

        $output->writeln(sprintf('Imported/updated %d track(s) from new releases.', $count));

        return Command::SUCCESS;
    }
}
