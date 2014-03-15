<?php

require_once 'lib/router.php';

class Extension_versatile_router extends Extension {
	private $_has_run = false;

	public function install() {
		Symphony::Configuration()->set('routes_file_path', 'routes.php', 'versatile_router');
		Symphony::Configuration()->set('disable_standard_routing', 'no', 'versatile_router');
		Administration::instance()->saveConfig();

		if (!file_exists(WORKSPACE . '/routes.php')) {
			// Copy routes file to workspace
			copy(EXTENSIONS . '/versatile_router/lib/routes.php', WORKSPACE . '/routes.php');
		}
	}

	public function uninstall() {
		Symphony::Configuration()->remove('versatile_router');
		Administration::instance()->saveConfig();
	}

	public function getSubscribedDelegates() {
		return array(
			array(
				'page' => '/system/preferences/',
				'delegate' => 'AddCustomPreferenceFieldsets',
				'callback' => 'appendPreferences'
			),
			array(
				'page' => '/system/preferences/',
				'delegate' => 'Save',
				'callback' => 'savePreferences'
			),
			array(
				'page' => '/frontend/',
				'delegate' => 'FrontendPrePageResolve',
				'callback' => 'frontendPrePageResolve'
			)
		);
	}

	public function appendPreferences($context) {
		$fieldset = new XMLElement('fieldset');
		$fieldset->setAttribute('class', 'settings');
		$fieldset->appendChild(new XMLElement('legend', 'Versatile Router'));
		$outer_div = new XMLElement('div', null, array('class' => 'two columns'));

		$div = new XMLElement('div', null, array('class' => 'column'));
		$input = Widget::Input(
			'settings[versatile_router][disable_standard_routing][', 'yes', 'checkbox'
			//$this->settingsPath('disable_standard_routing'), 'yes', 'checkbox'
		);
		if(Symphony::Configuration()->get('disable_standard_routing', 'versatile_router') == 'yes') {
			$input->setAttribute('checked', 'checked');
		}
		$div->appendChild(
			Widget::Label($input->generate() . __(' Disable standard routing'))
		);
		$div->appendChild(new XMLElement('p', __('By default, rerouting/redirection is performed only when the source path does not match a page. Check the box above to override this.'), array('class' => 'help')));
		$outer_div->appendChild($div);

		$div = new XMLElement('div', null, array('class' => 'column'));
		$div->appendChild(
			Widget::Label(
				__('Routes File Path'),
				Widget::Input('settings[versatile_router][routes_file_path]', Symphony::Configuration()->get('routes_file_path', 'versatile_router'))
			)
		);
		$div->appendChild(new XMLElement('p', __('Path relative to <code>workspace</code> for file holding route definitions.'), array('class' => 'help')));
		$outer_div->appendChild($div);

		$fieldset->appendChild($outer_div);
		$context['wrapper']->appendChild($fieldset);
	}

	public function savePreferences($context) {
		if(!isset($context['settings']['versatile_router']['disable_standard_routing'])) {
			$context['settings']['versatile_router']['disable_standard_routing'] = 'no';
		}
	}

	public function frontendPrePageResolve($context) {
		if($this->_has_run) return;
		$this->_has_run = true;
		$page = FrontEnd::Page()->resolvePage($context['page']);

		if(Symphony::Configuration()->get('disable_standard_routing', 'versatile_router') == 'yes' or empty($page)) {

			$route_to = versatile_router\Router::run();
			if($route_to) {
				$context['page'] = $route_to;
			}
			else {
				throw new FrontendPageNotFoundException();
			}
		}
	}
}
