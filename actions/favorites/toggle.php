<?php
/**
 * Toggle favorite items
 */

$guid = get_input('guid');

$entity = get_entity($guid);

if ($entity) {
	$user_guid = elgg_get_logged_in_user_guid();

	if (check_entity_relationship($entity->getGUID(), 'favorite', $user_guid)) {
		if (remove_entity_relationship($entity->getGUID(), 'favorite', $user_guid)) {
			system_message(elgg_echo("favorite:removed"));
		} else {
			register_error(elgg_echo("favorite:remove:error"));
		}
	} else {
		if (add_entity_relationship($entity->getGUID(), 'favorite', $user_guid)) {
			system_message(elgg_echo("favorite:added"));
		} else {
			register_error(elgg_echo("favorite:add:error"));
		}
	}
}

forward(REFERER);
