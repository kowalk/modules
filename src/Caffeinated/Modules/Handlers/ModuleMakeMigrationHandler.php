<?php
namespace Caffeinated\Modules\Handlers;

use Caffeinated\Modules\Modules;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModuleMakeMigrationHandler
{
	/**
	 * @var \Caffeinated\Modules\Modules
	 */
	protected $module;

	/**
	 * @var \Illuminate\Filesystem\Filesystem
	 */
	protected $finder;

	/**
	 * @var \Illuminate\Console\Command
	 */
	protected $console;

	/**
	 * @var string $moduleName The name of the module
	 */
	protected $moduleName;

	/**
	 * @var string $table The name of the table
	 */
	protected $table;

	/**
	 * @var string $migrationName The name of the migration
	 */
	protected $migrationName;

	/**
	 * @var string $className The name of the migration class
	 */
	protected $className;

	/**
	 * Constructor method.
	 *
	 * @param \Caffeinated\Modules\Modules      $module
	 * @param \Illuminate\Filesystem\Filesystem $finder
	 */
	public function __construct(Modules $module, Filesystem $finder)
	{
		$this->module = $module;
		$this->finder = $finder;
	}

	/**
	 * Fire off the handler.
	 *
	 * @param  \Caffeinated\Modules\Console\ModuleMakeMigrationCommand $console
	 * @param  string                                                  $slug
     * @param  string                                                  $table
     * @param  string                                                  $migrationName
     * @return string
     *
	 */
	public function fire(Command $console, $slug, $table, $migrationName = null)
	{
		$this->console       = $console;
		$this->moduleName    = Str::studly($slug);
		$this->table         = str_plural(strtolower($table));
		$this->migrationName = ($migrationName === null) ? 'create_'.snake_case($this->table).'_table' : snake_case($migrationName);
		$this->className     = studly_case($this->migrationName);

		if ($this->module->exists($this->moduleName)) {
			$this->makeFile($migrationName !== null);

			$this->console->info("Created Module Migration: [$this->moduleName] ".$this->getFilename());

			return exec('composer dump-autoload');
		}

		return $this->console->info("Module [$this->moduleName] does not exist.");
	}

	/**
	 * Create new migration file.
	 *
	 * @return int
	 */
	protected function makeFile($useEditStub = false)
	{
		return $this->finder->put($this->getDestinationFile(), $this->getStubContent($useEditStub));
	}

	/**
	 * Get file destination.
	 *
	 * @return string
	 */
	protected function getDestinationFile()
	{
		return $this->getPath().$this->formatContent($this->getFilename());
	}

	/**
	 * Get module migration path.
	 *
	 * @return string
	 */
	protected function getPath()
	{
		$path = $this->module->getModulePath($this->moduleName);

		return $path.'Database/Migrations/';
	}

	/**
	 * Get migration filename.
	 *
	 * @return string
	 */
	protected function getFilename()
	{
		return date("Y_m_d_His").'_'.$this->migrationName.'.php';
	}

	/**
	 * Get stub content.
	 *
	 * @return string
	 */
	protected function getStubContent($useEditStub = false)
	{
        $stubName = ($useEditStub) ? 'migrationedit.stub' : 'migration.stub';
        $stubPath = __DIR__.'/../Console/stubs/'.$stubName;

		return $this->formatContent($this->finder->get($stubPath));
	}

	/**
	 * Replace placeholder text with correct values.
	 *
	 * @param  string $content
	 * @return string
	 */
	protected function formatContent($content)
	{
		return str_replace(
			['{{migrationName}}', '{{table}}'],
			[$this->className, $this->table],
			$content
		);
	}
}
