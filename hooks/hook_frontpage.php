<?php
/**
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function selfregister_hook_frontpage(&$links) {
	assert('is_array($links)');
	assert('array_key_exists("links", $links)');

	$links['auth'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('selfregister/index.php'),
		'text' => '{selfregister:selfregister:link_panel}',
	);
}
