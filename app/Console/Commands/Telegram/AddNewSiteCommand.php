<?php

namespace App\Console\Commands\Telegram;

use App\Site;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Actions;
use Telegram\Bot\Commands\Command;

class AddNewSiteCommand extends Command
{
	/**
	 * @var string Command Name
	 */
	protected $name = "adicionar";

	/**
	 * @var string Command Description
	 */
	protected $description = "Add a new site to Ahoy!";
	/**
	 * @inheritdoc
	 */
	public function handle($arguments)
	{

		if ( ! Cache::has( 'site-' . $arguments ) ) {
			$this->replyWithMessage("Não foi encontrado nenhum site com esse argumento.");
			return;
		}

		$site = Cache::get( 'site-' . $arguments );

		// Validate if the URL isn't on the database yet
		if( Site::where('url','=',$site)->first() != null ) {
			$this->replyWithMessage("O site $site já se encontra na base de dados.");
			return;
		}

		$site_obj = new Site();
		$site_obj->url = $site;
		$site_obj->save();

		$this->replyWithMessage( $site . " foi adicionado à base de dados.", true);

		// Notify the sitesbloqueados.pt about the new site
		$url = 'https://sitesbloqueados.pt/wp-json/ahoy/refresh';

		$cmd = "curl -X GET -H 'Content-Type: application/json'";
		$cmd.= " " . "'" . $url . "'";

		$cmd .= " > /dev/null 2>&1 &";

		exec($cmd, $output);

		// Flush the PAC cache
		Cache::tags(['generate_pac'])->flush();

		// Remove the cache
		Cache::forget('site-' . $arguments );

	}
}