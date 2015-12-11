<?php

class Controller {

	protected $layout = 'default';

	public function __construct() {
		$f3=Base::instance();
		$this->f3 = $f3;

		// Connect to the database
		$this->db = new Database();
		$this->Model = new Model($this);

		//Load helpers
		$helpers = array('Auth');
		foreach($helpers as $helper) {
			$helperclass = $helper . "Helper";
			$this->$helper = new $helperclass($this);
		}
	}

	public function beforeRoute($f3) {
		$settings = $this->Model->Settings;
        $debug = $settings->getSetting('debug');
        //CSRF PROTECTION SHOULD BE DISABLED IN DEBUG MODE, SO CHECK IT 
        if ($debug != 1) {    
            //GET THE URL USER REQUESTED USING HTTP HEADER
            $request = $f3->get('SERVER')["REQUEST_URI"];
            //LIST OF PAGES AND ACTIONS SHOULD NOT BE ACCESSED THROUGH A LINK DIRECTLY
            $blockedPages = array("#/blog/moderate/#", "#/admin/page/delete/#", "#admin/blog/delete/#", "#admin/category/delete/#", "#admin/user/delete/#", "#user/logout#", "#admin/comment/edit/#" );
            //GO THROUGH THE LIST OF BLACKLIST
            foreach ($blockedPages as $key => $value) {
                //IF THE REQUESTED URL MATCHES ONE OF THE URLS FROM THE BLACKLIST
                if (preg_match($value, $request)) {    
                    //CHECK IF USER CAME FROM NOWHERE
                    if (isset($f3->get('SERVER')['HTTP_REFERER'])) {
                        //CHECK IF THE URL IS EXTERNAL 
                        if (parse_url($f3->get('SERVER')['HTTP_REFERER'])["host"] != $f3->get('SERVER')['SERVER_NAME']) {
                            //IF USER CAME FROM EXTERNAL URL, DANGEROUS, SEND HOME
                            $f3->reroute('/');
                        }
                    } else {
                        //IF FROM NOWHERE, SUSPICIOUS, SEND HOME
                        $f3->reroute('/');
                    }
                }
            }
        }
		
		
		// clean the post tags except thoes ones in the list below;
		$_POST=$f3->clean($_POST, 'strong, em, body, s, u, sub, sup, p, ol, li, blockquote, span, a, table, tbody, tr, td, img, flash');
		$this->request = new Request();

		//Check user
		$this->Auth->resume();

		//Load settings
		$settings = $this->Model->Settings->fetchList(array('setting','value'));
		$settings['base'] = $f3->get('BASE');
		
		//Append debug mode to title
		if($settings['debug'] == 1) { $settings['name'] .= ' (Debug Mode)'; }

		$settings['path'] = $f3->get('PATH');
		$this->Settings = $settings;
		$f3->set('site',$settings);
		
		$parameters = $f3->get('PARAMS');
		// clean the perameters tags except thoes ones in the list below;
        $parameters = $f3->clean($parameters, 'strong, em, body, s, u, sub, sup, p, ol, li, blockquote, span, a, table, tbody, tr, td, img, flash');
        $f3->set('PARAMS', $parameters);

				//Extract request data
		extract($this->request->data);

		//Process before route code
		if(isset($beforeCode)) {
			$f3->process($beforeCode);
		}
	}

	public function afterRoute($f3) {	
		//Set page options
		$f3->set('title',isset($this->title) ? $this->title : get_class($this));

		//Prepare default menu	
		$f3->set('menu',$this->defaultMenu());

		//Setup user
		$f3->set('user',$this->Auth->user());

		//Check for admin
		$admin = false;
		if(stripos($f3->get('PARAMS.0'),'admin') !== false) { $admin = true; }

		//Identify action
		$controller = get_class($this);
		if($f3->exists('PARAMS.action')) {
			$action = $f3->get('PARAMS.action');	
		} else {
			$action = 'index';
		}

		//Handle admin actions
		if ($admin) {
			$controller = str_ireplace("Admin\\","",$controller);
			$action = "admin_$action";
		}

		//Handle errors
		if ($controller == 'Error') {
			$action = $f3->get('ERROR.code');
		}

		//Handle custom view
		if(isset($this->action)) {
			$action = $this->action;
		}

		//Extract request data
		extract($this->request->data);

		//Generate content		
		$content = View::instance()->render("$controller/$action.htm");
		$f3->set('content',$content);

		//Process before route code
		if(isset($afterCode)) {
			$f3->process($afterCode);
		}

		//Render template
		echo View::instance()->render($this->layout . '.htm');
	}

	public function defaultMenu() {
		$menu = array(
			array('label' => 'Search', 'link' => 'blog/search'),
			array('label' => 'Contact', 'link' => 'contact'),
		);

		//Load pages
		$pages = $this->Model->Pages->fetchAll();
		foreach($pages as $pagetitle=>$page) {
			$pagename = str_ireplace(".html","",$page);
			$menu[] = array('label' => $pagetitle, 'link' => 'page/display/' . $pagename);
		}

		//Add admin menu items
		if ($this->Auth->user('level') > 1) {
			$menu[] = array('label' => 'Admin', 'link' => 'admin');
		}

		return $menu;
	}

}

?>
