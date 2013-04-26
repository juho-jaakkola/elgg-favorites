<?php

elgg_register_event_handler('init', 'system', 'favorites_init');

/**
 * Initialize the plugin
 */
function favorites_init () {
	elgg_register_plugin_hook_handler('register', 'menu:entity', 'favorites_entity_menu');
	elgg_register_plugin_hook_handler('register', 'menu:user_hover', 'favorites_user_hover_menu');
	elgg_register_plugin_hook_handler('register', 'menu:topbar', 'favorites_topbar_menu');
	elgg_register_plugin_hook_handler('setting', 'plugin', 'favorites_handle_plugin_settings');

	$actions_path = elgg_get_plugins_path() . 'favorites/actions/';
	elgg_register_action('favorite/toggle', $actions_path . 'favorites/toggle.php');

	elgg_register_page_handler('favorites', 'favorites_page_handler');
}

/**
 * Add favorites icon to entity menu
 * 
 * @param  string $hook   'register'
 * @param  string $type   'menu:entity'
 * @param  array  $return Array of ElggMenuItem objects
 * @param  array  $params View vars
 * @return array  $return Array of ElggMenuItem objects
 */
function favorites_entity_menu ($hook, $type, $return, $params) {
	if (!elgg_is_logged_in()) {
		return $return;
	}

	$entity = $params['entity'];

	// Allow favoring only the types and subtype defined in plugin settings
	$supported_types = unserialize(elgg_get_plugin_setting('entity_types', 'favorites'));

	if (in_array($entity->getType(), $supported_types) ||
		in_array($entity->getSubtype(), $supported_types)) {

		$user_guid = elgg_get_logged_in_user_guid();

		// Own contents cannot be added to favorites
		if ($entity->getOwnerGUID() == $user_guid) {
			return $return;
		}

		if (check_entity_relationship($entity->getGUID(), 'favorite', $user_guid)) {
			$icon = elgg_view_icon('star-alt');
			$title = elgg_echo('favorites:remove');
		} else {
			$icon = elgg_view_icon('star-empty');
			$title = elgg_echo('favorites:add');
		}

		$return[] = ElggMenuItem::factory(array(
			'name' => 'favorite_toggle',
			'text' => $icon,
			'href' => "action/favorite/toggle?guid={$entity->getGUID()}",
			'title' => $title,
			'is_action' => true,
			'priority' => 1002, // Displays after "n likes"
		));
	}

	return $return;
}

/**
 * Add favorites link to user hover menu
 * 
 * @param  string $hook   'register'
 * @param  string $type   'menu:user_hover'
 * @param  array  $return Array of ElggMenuItem objects
 * @param  array  $params View vars
 * @return array  $return Array of ElggMenuItem objects
 */
function favorites_user_hover_menu ($hook, $type, $return, $params) {
	$user = $params['entity'];

	$return[] = ElggMenuItem::factory(array(
		'name' => 'favorites',
		'text' => elgg_echo('favorites'),
		'href' => "favorites/{$user->username}",
	));

	return $return;
}

/**
 * Add favorites link to topbar menu
 * 
 * @param  string $hook   'register'
 * @param  string $type   'menu:topbar'
 * @param  array  $return Array of ElggMenuItem objects
 * @param  array  $params View vars
 * @return array  $return Array of ElggMenuItem objects
 */
function favorites_topbar_menu ($hook, $type, $return, $params) {
	$user = elgg_get_logged_in_user_entity();

	$return[] = ElggMenuItem::factory(array(
		'name' => 'favorites',
		'text' => elgg_echo('favorites:own'),
		'href' => "favorites/{$user->username}",
		'priority' => 100,
	));

	return $return;
}

/**
 * Display page with all of users favorites
 */
function favorites_page_handler ($page) {
	gatekeeper();

	if (empty($page[0])) {
		$user = elgg_get_logged_in_user_entity();
	} else {
		$user = get_user_by_username($page[0]);

		if (!$user) {
			register_error(elgg_echo('favorites:notfound'));
			forward(REFERER);
		}
	}

	if ($user->guid == elgg_get_logged_in_user_guid()) {
		$title = elgg_echo('favorites:own');
	} else {
		$title = elgg_echo('favorites:user', array($user->name));
	}

	$content = elgg_list_entities_from_relationship(array(
		'relationship_guid' => $user->getGUID(),
		'relationship' => 'favorite',
		'inverse_relationship' => true,
		'full_view' => false,
		'order_by' => 'r.time_created DESC',
	));

	if (!$content) {
		$content = elgg_echo('favorites:none');
	}

	$params = array(
		'title' => 	$title,
		'content' => $content,
		'filter' => '',
	);

	$body = elgg_view_layout('content', $params);
	echo elgg_view_page($title, $body);
}

/**
 * Private settings do not support arrays so we serialize it to a string
 * 
 * @return array
 */
function favorites_handle_plugin_settings ($hook, $type, $return, $params) {
	return serialize($params['value']);
}