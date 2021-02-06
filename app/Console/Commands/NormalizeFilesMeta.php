<?php

namespace App\Console\Commands;

use App\Models\Files\File;
use Illuminate\Console\Command;

class NormalizeFilesMeta extends Command
{
	protected $signature = 'files:normalize-meta';

	protected $description = 'Command description';

	public function handle()
	{

		$query = File::query()
			->whereIn('type', ['image', 'video']);

		$progress = $this->getOutput()->createProgressBar($query->count());
		$progress->start();

		$query->chunk(100,
			/**
			 *
			 * @var File[] $files
			 */
			function ($files) use ($progress) {

				foreach ($files as $file) {

					$width  = null;
					$height = null;

					if (isset($file->meta['dimensions'])) {
						if (isset($file->meta['dimensions']['hd'])) {
							$width  = $file->meta['dimensions']['hd'][0];
							$height = $file->meta['dimensions']['hd'][1];
						} elseif (isset($file->meta['dimensions']['sd'])) {
							$width  = $file->meta['dimensions']['sd'][0];
							$height = $file->meta['dimensions']['sd'][1];
						}
					}

					if (!$width && !$height) {
						$progress->advance();
						continue;
					}

					$file->update([
						'meta' => [
							'width'  => $width,
							'height' => $height,
						],
					]);

					$progress->advance();

				}

			});


		$progress->finish();

	}
}