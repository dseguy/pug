<?php

/*
 * This file is part of Pug
 */
namespace Pug;

define('PUG_CONFIG', getenv('HOME').DIRECTORY_SEPARATOR.'.pug');

class Config
{
	/**
	 * @var array
	 */
	protected $projects=[];

	/**
	 * @param	array	$projects	Array of Pug\Project objects
	 * @return	void
	 */
	public function __construct(array $projects)
	{
		$this->projects = $projects;
	}

	/**
	 * @param	Project	$project
	 */
	public function addProject(Project $project)
	{
		foreach($this->projects as $current)
		{
			if($project->getName() == $current->getName())
			{
				throw new \Huxtable\Application\Command\CommandInvokedException("The project '{$project->getName()}' already exists. Choose another name or see 'pug help project'", 1);
			}
		}

		$this->projects[] = $project;
		$this->write();
	}

	/**
	 * @param	string	$name
	 * @return
	 */
	public function getProject($name)
	{
		foreach($this->projects as $project)
		{
			if($project->getName() == $name)
			{
				return $project;
			}
		}

		throw new \Huxtable\Application\Command\CommandInvokedException("Project '{$name}' not found", 1);
	}
	/**
	 * @return	array					Return contents of $this->projects
	 */
	public function getProjects()
	{
		return $this->projects;
	}

	/**
	 * @return	Pug\Config
	 */
	public static function open()
	{
		$projects = [];
		$fileInfo = new \SplFileInfo(PUG_CONFIG);

		if(!file_exists(PUG_CONFIG))
		{
			touch($fileInfo->getPathname());
		}
		else
		{
			if(!is_readable(PUG_CONFIG))
			{
				throw new \Huxtable\Application\Command\CommandInvokedException("Can't read from ~/.pug", 1);
			}
			if(!is_writable(PUG_CONFIG))
			{
				throw new \Huxtable\Application\Command\CommandInvokedException("Can't write to ~/.pug", 1);
			}

			$json = json_decode(file_get_contents(PUG_CONFIG), true);

			if(isset($json['projects']))
			{
				foreach($json['projects'] as $project)
				{
					$projects[] = new Project($project['name'], $project['path']);
				}
			}
		}

		return new self($projects);
	}

	/**
	 */
	protected function write()
	{
		$projects = $this->projects;

		$json = json_encode(compact('projects'), JSON_PRETTY_PRINT);

		file_put_contents(PUG_CONFIG, $json);
	}
}

?>
