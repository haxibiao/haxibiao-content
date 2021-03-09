<?php


namespace Haxibiao\Content\Console;

use Haxibiao\Content\Cache;
use Illuminate\Console\Command;

class ClearCache extends Command
{

	protected $signature = 'page-cache:clear {slug? : URL slug of page/directory to delete} {--recursive}';

	protected $description = 'Clear (all or part of) the page cache.';

	public function handle()
	{
		$cache = $this->laravel->make(Cache::class);
		$recursive = $this->option('recursive');
		$slug = $this->argument('slug');

		if (!$slug) {
			$this->clear($cache);
		} else if ($recursive) {
			$this->clear($cache, $slug);
		} else {
			$this->forget($cache, $slug);
		}
	}

	public function forget(Cache $cache, $slug)
	{
		if ($cache->forget($slug)) {
			$this->info("Page cache cleared for \"{$slug}\"");
		} else {
			$this->info("No page cache found for \"{$slug}\"");
		}
	}

	public function clear(Cache $cache, $path = null)
	{
		if ($cache->clear($path)) {
			$this->info('Page cache cleared at '.$cache->getCachePath($path));
		} else {
			$this->warn('Page cache not cleared at '.$cache->getCachePath($path));
		}
	}
}
