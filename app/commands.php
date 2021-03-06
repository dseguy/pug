<?php

/*
 * This file is part of Pug
 */

use \Huxtable\Format;
use \Huxtable\Output;
use \Huxtable\Command\CommandInvokedException;

$commands = [];

// --
// !add
// --
$add = new Huxtable\Command('add', 'Start tracking a new project', function( $name, $path )
{
	$file = new SplFileInfo($path);

	// Resolve $path
	if(!file_exists($file->getRealPath()))
	{
		throw new CommandInvokedException( "Couldn't track project, path '{$path}' not found", 1 );
	}
	if(!$file->isDir())
	{
		throw new CommandInvokedException( "Couldn't track project, path '{$path}' not a directory", 1 );
	}

	$pug = new Pug\Pug();
	$pug->addProject( new Pug\Project( $name, $file->getRealPath(), true, $file->getCTime() ) );

	return listProjects($pug->getProjects());
});

$add->addAlias( 'track' );

$commands['add'] = $add;

// --
// !disable
// --
$disable = new Huxtable\Command('disable', 'Exclude project from \'all\' updates', function( $name )
{
	$pug = new Pug\Pug();
	$pug->disableProject( $name );

	return listProjects( $pug->getProjects() );
});

$commands['disable'] = $disable;

// --
// !enable
// --
$enable = new Huxtable\Command('enable', 'Include project in \'all\' updates', function( $name )
{
	$pug = new Pug\Pug();
	$pug->enableProject( $name );

	return listProjects( $pug->getProjects() );
});

$commands['enable'] = $enable;

// --
// !rm
// --
$rm = new Huxtable\Command('rm', 'Stop tracking a project', function( $name )
{
	$pug = new Pug\Pug();
	$pug->removeProject($name);

	return listProjects ($pug->getProjects());
});

$rm->addAlias( 'remove' );
$rm->addAlias( 'untrack' );

$commands['rm'] = $rm;

// --
// !show
// --
$show = new Huxtable\Command('show', 'Show tracked projects', function( $name='' )
{
	$output = new Output();
	$pug = new Pug\Pug();
	$projects = $pug->getProjects ($this->getOptionValue('t'));

	if (count ($projects) < 1)
	{
		$output->line ('pug: Not tracking any projects. See \'pug help\'');
	}
	else
	{
		$output->string ( listProjects ( $pug->getProjects(), $name ) );
	}

	return $output->flush();
});

$show->addAlias('list');
$show->addAlias('ls');

$showUsage = <<<USAGE
show [options] [<name>]

OPTIONS
     -t  sort by time updated, recently updated first


USAGE;

$show->setUsage($showUsage);

$show->registerOption('t', 'Sort by time modified (most recently modified first) before sorting projects by name');

$commands['show'] = $show;

// --
// !update
// --
$update = new Huxtable\Command('update', 'Fetch project updates', function()
{
	$pug = new Pug\Pug();
	$sources = func_get_args();

	if( count( $sources ) == 0 )
	{
		$sources[] = '.';
	}

	for( $i=0; $i < count ($sources); $i++ )
	{
		$pug->update( $sources[$i] );
	}
});

$update->addAlias('up');
$update->setUsage("update [all|<path>|<project>...]");

$commands['update'] = $update;

/**
 * @param	array	$projects
 * @param	string	$name
 */
function listProjects( array $projects, $name='' )
{
	if (count ($projects) < 1)
	{
		return;
	}

	$output = new Output;

	// List all projects
	if( strlen( $name ) == 0 )
	{
		foreach($projects as $project)
		{
			$output->line (sprintf
			(
				'%s %s'
				, $project->isEnabled() ? Output::colorize( '*', 'green' ) : ' '
				, $project->getName()
			));
		}
	}
	else
	{
		$listed = false;

		foreach($projects as $project)
		{
			if( $project->getName() == $name )
			{
				$updated = is_null ($project->getUpdated()) ? '-' : Format::date ($project->getUpdated());
				$path = str_replace (getenv('HOME'), '~', $project->getPath());

				$output->line (sprintf
				(
					'%s %-12s  %s'
					, $project->isEnabled() ? Output::colorize( '*', 'green' ) : ' '
					, $updated
					, $path
				));
				

				$listed = true;
			}
		}

		if( !$listed )
		{
			throw new CommandInvokedException( "Project '{$name}' not found", 1 );
		}
	}

	return $output->flush();
}

?>
