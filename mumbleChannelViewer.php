<?php
/**
 * Helper class for the Mumble Channel Viewer module
 *
 * @package MumbleChannelViewer
 * @author Mike Johnson <mikej@commandchannel.com>; Doug Gilbert <gilbert.159@osu.edu>
 * @copyright Copyright (c) 2011, Command Channel Corporation
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version 1.0
 */
class MumbleChannelViewer
{
	/**
	 * @since 1.0
	 * @todo Add XML format support and input validation.
	 * @param string $dataUri URI that will return information about a Mumble server.
	 * @param string $dataFormat The format the data will be in (i.e. xml or json). The default value is json.
	 * @return string An HTML unordered list containing all of the channels and users currently connected to the Mumble server.
	*/
	function render($dataUri, $dataFormat = "json")
	{
		if ($dataFormat == "json")
			return self::renderJson($dataUri);
		else if ($dataFormat == "xml")
			die("The XML format is not supported yet.");
		else
			die("The data format {$dataFormat} is not supported.");
	}

	/**
	 * @since 1.0
	 * @param string $jsonUri URI that will return information about a Mumble server in JSON format.
	 * @return string An HTML unordered list containing all of the channels and users currently connected to the Mumble server.
	*/
	protected static function renderJson($jsonUri)
	{
		$jsonRaw = file_get_contents($jsonUri);
		$jsonDecoded = json_decode($jsonRaw, true);

		return self::renderChannel($jsonDecoded["root"], true);
	}

	/**
	 * Recursively parses a channel and compiles information about subchannels and users.
	 * @since 1.0
	 * @param array $currentChannel The channel to parse.
	 * @param bool $renderUl True if an opening UL tag has aleady been generated (i.e. this usually happens when $currentChannel is not the first channel in the list of subchannels); otherwise, false.
	 * @return string An HTML unordered list containing all of the subchannels and users.
	*/
	protected static function renderChannel($currentChannel, $renderUl) {
		$output = null;
		if ($renderUl)
			$output .= "<ul>";

		$output .= "<li><a href='{$currentChannel["x_connecturl"]}'>{$currentChannel["name"]}</a>";		// Start of the LI element for this channel

		$subStarted = false;

		foreach ($currentChannel["channels"] as $subChannel) {
			$output .= self::renderChannel($subChannel, !$subStarted);		// recursively render each subchannel
		}

		if (count($currentChannel["users"]) > 0) {		// render each user for the current channel
			if (!$subStarted) {		// If there were no subchannels, and there are users in this channel, then we need to open a new UL element to hold the users.
				$output .= "<ul>";
				$subStarted = true;
			}

			foreach ($currentChannel["users"] as $currentUser) {
				$output .= self::renderUser($currentUser);
			}
		}

		if ($subStarted)
			$output .= "</ul>";		// Closes the child UL element (used if there are channels below this one or users in this channel).

		$output .= "</li>";		// End of the LI element for this channel

		if ($renderUl)
			$output .= "</ul>";

		return $output;
	}

	/**
	 * Parses user information (such as mute and deafened status).
	 * @since 1.0
	 * @param array $user The user to parse.
	 * @return string An HTML list item element that contains the user's name and any relevant status icons.
	*/
	protected static function renderUser($user) {
		$output = "<li>";
		if ($user["userid"] > 0)
			$output .= "<img src='modules/mod_mumbleViewer/images/authenticated.png' alt='Authenticated' />";
		if ($user["suppress"])
			$output .= "<img src='modules/mod_mumbleViewer/images/muted_suppressed.png' alt='Suppressed' />";
		if ($user["selfDeaf"])
			$output .= "<img src='modules/mod_mumbleViewer/images/deafened_self.png' alt='Self-Deafened' />";
		if ($user["deaf"])
			$output .= "<img src='modules/mod_mumbleViewer/images/deafened_server.png' alt='Server-Deafened' />";
		if ($user["selfMute"])
			$output .= "<img src='modules/mod_mumbleViewer/images/muted_self.png' alt='Self-Muted' />";
		if ($user["mute"])
			$output .= "<img src='modules/mod_mumbleViewer/images/muted_server.png' alt='Server-Muted' />";
		$output .= "<span>{$user["name"]}</span></li>";

		return $output;
	}
}
?>
