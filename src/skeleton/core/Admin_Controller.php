<?php
/**
 * CodeIgniter Skeleton
 *
 * A ready-to-use CodeIgniter skeleton  with tons of new features
 * and a whole new concept of hooks (actions and filters) as well
 * as a ready-to-use and application-free theme and plugins system.
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2018, Kader Bouyakoub <bkader@mail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package 	CodeIgniter
 * @author 		Kader Bouyakoub <bkader@mail.com>
 * @copyright	Copyright (c) 2018, Kader Bouyakoub <bkader@mail.com>
 * @license 	http://opensource.org/licenses/MIT	MIT License
 * @link 		https://github.com/bkader
 * @since 		1.0.0
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_Controller Class
 *
 * Controllers extending this class requires a logged in user of rank "admin".
 *
 * @package 	CodeIgniter
 * @subpackage 	Skeleton
 * @category 	Core Extension
 * @author 		Kader Bouyakoub <bkader@mail.com>
 * @link 		https://github.com/bkader
 * @copyright	Copyright (c) 2018, Kader Bouyakoub (https://github.com/bkader)
 * @since 		1.0.0
 * @since 		1.3.3 	Added dynamic assets loading.
 * 
 * @version 	1.3.3
 */
class Admin_Controller extends User_Controller
{
	/**
	 * Array of CSS files to be loaded.
	 *
	 * @since 	1.3.3
	 * 
	 * @var 	array
	 */
	protected $styles = array(
		'font-awesome',
		'bootstrap',
		'toastr',
		'admin',
	);

	/**
	 * Array of JS files to be loaded.
	 *
	 * @since 	1.3.3
	 *
	 * @var 	array
	 */
	protected $scripts = array(
		'modernizr-2.8.3',
		'jquery-3.2.1',
		'bootstrap',
		'toastr',
		'bootbox',
		'admin',
	);

	/**
	 * Class constructor
	 *
	 * @since 	1.0.0
	 * @since 	1.3.3 	Added favicon to dashboard, removed loading admin language file
	 *         			and move some actions to "_remap" method.
	 * 
	 * @return 	void
	 */
	public function __construct()
	{
		parent::__construct();

		// Make sure the user is an administrator.
		if ( ! $this->auth->is_admin())
		{
			set_alert(lang('error_permission'), 'error');
			redirect('');
			exit;
		}

		// Load admin helper.
		$this->load->helper('admin');
	}

	// ------------------------------------------------------------------------

	/**
	 * We remap methods so we can do extra actions when we are not on methods
	 * that required AJAX requests.
	 *
	 * @since 	1.3.3
	 *
	 * @access 	public
	 * @param 	string 	$method 	The method's name.
	 * @param 	array 	$params 	Arguments to pass to the method.
	 * @return 	mixed 	Depends on the called method.
	 */
	public function _remap($method, $params = array())
	{
		// If on a method that does no require AJAX request.
		if ( ! in_array($method, $this->ajax_methods) 
			&& ! in_array($method, $this->safe_ajax_methods))
		{
			// Print admin head part.
			add_filter('admin_head', array($this, 'admin_head'));

			// Prepare dashboard sidebar.
			$this->theme->set('admin_menu', $this->_admin_menu(), true);

			// We add favicon.
			$this->theme->add_meta(
				'icon',
				$this->theme->common_url('img/favicon.ico'),
				'rel',
				'type="image/x-icon"'
			);

			// We remove Modernizr and jQuery to dynamically load them.
			$this->theme->remove('js', 'modernizr', 'jquery');

			// Should we compress files?
			$compress = (ENVIRONMENT === 'production') ? '1' : '';

			// Do we have any CSS files to load?
			if ( ! empty($this->styles))
			{
				$this->styles = array_map('trim', $this->styles);
				$this->styles = array_filter($this->styles);
				$this->styles = array_unique($this->styles);
				$this->styles = implode(',', $this->styles);

				// Right-To-Left languages.
				('rtl' === langinfo('direction')) && $this->styles .= ',bootstrap-rtl,admin-rtl';

				$this->theme
					->no_extension()
					->add('css', site_url("load/styles?c={$compress}&load=".$this->styles));
			}

			// Do we have any JS files to laod?
			if ( ! empty($this->scripts))
			{
				$this->scripts = array_map('trim', $this->scripts);
				$this->scripts = array_filter($this->scripts);
				$this->scripts = array_unique($this->scripts);
				$this->scripts = implode(',', $this->scripts);
				$this->theme
					->no_extension()
					->add('js', site_url("load/scripts?c={$compress}&load=".$this->scripts));
			}

			// We call the method.
			return call_user_func_array(array($this, $method), $params);
		}

		// Otherwise, we let the parent handle the rest.
		return parent::_remap($method, $params);
	}

	// ------------------------------------------------------------------------

	/**
	 * Loads jQuery UI.
	 * @access 	protected
	 * @return 	void
	 */
	protected function load_jquery_ui()
	{
		$this->theme
			->add('css', get_common_url('css/jquery-ui'), 'jquery-ui')
			->add('js', get_common_url('js/jquery-ui'), 'jquery-ui');
	}

	// ------------------------------------------------------------------------

	/**
	 * Generates a JQuery content fot draggable items.
	 *
	 * @since 	1.0.0
	 * @since 	1.3.0 	Added jquery.touch-punch so that sortable elements
	 *         			work on mobile devices.
	 *
	 * @access 	protected
	 * @param 	string 	$button 			the button that handles saving.
	 * @param 	string 	$target 			The element id or class to target.
	 * @param 	string 	$url 				The URL used to send AJAX request.
	 * @param 	string 	$success_message 	The message to be displayed after success.
	 * @param 	string 	$error_message 		The message to be displayed after success.
	 * @return 	void
	 */
	protected function add_sortable_list($button, $target, $url, $success_message = null, $error_message = null)
	{
		// If these element are not provided, nothing to do.
		if (empty($button) OR empty($target) OR empty($url))
		{
			return;
		}

		// Prepare the script to output.
		$script =<<<EOT
\n\t<script>
	var data = data || [];
	jQuery('{$target}').sortable({
		axis: 'y',
		update: function (event, ui) {
			data = jQuery(this).sortable('serialize');
		}
	});
	jQuery(document).on('click', '{$button}', function(e) {
		e.preventDefault();
		if (data.length) {
			jQuery.ajax({
				data: data,
				type: 'POST',
				url: '{$url}',
				success: function(response) {
					response = jQuery.parseJSON(response);
					if (response.status == true) {
						toastr.success('{$success_message}');
					} else {
						toastr.error('{$error_message}');
					}
				}
			});
		}
	});
	</script>
EOT;

		// We make sure to load jQuery UI then output script.
		$this->theme
			->add('css', get_common_url('css/jquery-ui'), 'jquery-ui')
			->add('js', get_common_url('js/jquery-ui'), 'jquery-ui')
			->add('js', get_common_url('js/jquery.ui.touch-punch'), 'touch-punch')
			->add_inline('js', $this->theme->compress_output($script));
	}

	// ------------------------------------------------------------------------
	// Private methods.
	// ------------------------------------------------------------------------

	/**
	 * Added some needed scripts to the head section.
	 *
	 * @since 	1.3.3
	 *
	 * @access 	public
	 * @param 	string 	$output
	 * @return 	string
	 */
	public function admin_head($output)
	{
		// JavaScript opening tag.
		$output .= "\t".'<script type="text/javascript">';

		// Creating Kbcore object.
		$output .= 'var Kbcore = {}';

		// Adding configuration.
		$config = json_encode(array(
			'siteURL'    => site_url(),
			'baseURL'    => base_url(),
			'adminURL'   => admin_url(),
			'currentURL' => current_url(),
			'ajaxURL'    => ajax_url('admin'),
			'lang'       => $this->lang->languages($this->session->language),
		));
		$output .= ", Config = {$config}";

		// Object for later use.
		$output .= ', i18n = {};';

		// JavaScript closing tag and IE9 support.
		$output .= '</script>'."\n";
		add_ie9_support($output, (ENVIRONMENT === 'production'));

		return $output;
	}

	// ------------------------------------------------------------------------

	/**
	 * Prepare dashboard sidebar menu.
	 * @access 	public
	 * @param 	none
	 * @return 	array
	 */
	protected function _admin_menu()
	{
		$menu = array();
		$modules = $this->router->list_modules(true);

		// Sort modules.
		uasort($modules, function($a, $b) {
			return $a['admin_order'] - $b['admin_order'];
		});

		foreach ($modules as $folder => $details)
		{
			if ($this->router->has_admin($folder))
			{
				$menu[$folder] = $details['admin_menu'];
			}
		}

		return $menu;
	}

}
