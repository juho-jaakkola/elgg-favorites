<?php
/**
 * Favorites plugin settings
 */

$types_label = elgg_echo('favorites:settings:types');

$types = get_registered_entity_types();

$options = array();
foreach ($types as $type => $subtypes) {
	if ($type == 'user') {
		continue;
	}

	if ($type != 'object') {
		$label = elgg_echo("item:$type");
		$options[$label] = $type;
	}

	foreach($subtypes as $subtype) {
		$subtype_label = elgg_echo("item:$type:$subtype");
		$options[$subtype_label] = $subtype;
	}
}

$types_input = elgg_view('input/checkboxes', array(
	'name' => 'params[entity_types]',
	'options' => $options,
	'value' => unserialize($vars['entity']->entity_types)
));

echo <<<FORM
	<div>
		<label>$types_label</label>
		$types_input
	</div>
FORM;
