<?php
class User extends Controller {

	public function view($f3) {
		$userid = $f3->get('PARAMS.3');
		
		// Provent SQL Injection by filtering data
		$userid = (integer)$userid;
		$u = $this->Model->Users->fetch($userid);
		if(empty($u)){
                \StatusMessage::add('Invalid post name','danger');
                return $f3->reroute('/admin/category');
            }

		$articles = $this->Model->Posts->fetchAll(array('user_id' => $userid));
		$comments = $this->Model->Comments->fetchAll(array('user_id' => $userid));

		$f3->set('u',$u);
		$f3->set('articles',$articles);
		$f3->set('comments',$comments);
	}

	public function add($f3) {
		if($this->request->is('post')) {
$settings = $this->Model->Settings;
            $debug = $settings->getSetting('debug');

            if ($debug != 1) {                    
                $captcha=$_POST['g-recaptcha-response'];
                if(empty($captcha)) { 
                    StatusMessage::add('Enter captcha please','danger');
                    $f3->reroute('/');
                }
                $response=file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=6Ld6wBITAAAAAOSA4kywtpw1UbJExz1jz-j0EAyp&response=".$captcha);        
                $response = json_decode($response, true);
                if ($response["success"]==false) {
                    StatusMessage::add('No entry for spammers, sorry','danger');
                    $f3->reroute('/');
                }
            }
			extract($this->request->data);
			$check = $this->Model->Users->fetch(array('username' => $username));
			if (!empty($check)) {
				StatusMessage::add('User already exists','danger');
			} else if($password != $password2) {
				StatusMessage::add('Passwords must match','danger');
			} else {
				$user = $this->Model->Users;
				$user->copyfrom('POST');
				$user->created = mydate();
				$user->bio = '';
				$user->level = 1;
				$user->setPassword($password);
				if(empty($displayname)) {
					$user->displayname = $user->username;
				}

				//Set the users password
				$user->setPassword($user->password);

				$user->save();	
				StatusMessage::add('Registration complete','success');
				return $f3->reroute('/user/login');
			}
		}
	}

	public function login($f3) {
		/** YOU MAY NOT CHANGE THIS FUNCTION - Make any changes in Auth->checkLogin, Auth->login and afterLogin() */
		if ($this->request->is('post')) {

			//Check for debug mode
			$settings = $this->Model->Settings;
			$debug = $settings->getSetting('debug');

			//Either allow log in with checked and approved login, or debug mode login
			list($username,$password) = array($this->request->data['username'],$this->request->data['password']);
			if (
				($this->Auth->checkLogin($username,$password,$this->request,$debug) && ($this->Auth->login($username,$password))) ||
				($debug && $this->Auth->debugLogin($username))) {

					$this->afterLogin($f3);

			} else {
				StatusMessage::add('Invalid username or password','danger');
			}
		}		
	}

	/* Handle after logging in */
	private function afterLogin($f3) {
				StatusMessage::add('Logged in succesfully','success');

				//Redirect to where they came from
				if(isset($_GET['from'])) {
					$f3->reroute($_GET['from']);
				} else {
					$f3->reroute('/');	
				}
	}

	public function logout($f3) {
		$this->Auth->logout();
		StatusMessage::add('Logged out succesfully','success');
		$f3->reroute('/');	
	}


/*
	public function profile($f3) {	
		$id = $this->Auth->user('id');
		extract($this->request->data);
		$u = $this->Model->Users->fetch($id);
		if($this->request->is('post')) {
			$u->copyfrom('POST');

			//Handle avatar upload
			if(isset($_FILES['avatar']) && isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name'])) {
				$url = File::Upload($_FILES['avatar']);
				$u->avatar = $url;
			} else if(isset($reset)) {
				$u->avatar = '';
			}

			$u->save();
			\StatusMessage::add('Profile updated succesfully','success');
			return $f3->reroute('/user/profile');
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}
*/

	public function profile($f3) {	
		$id = $this->Auth->user('id');
		extract($this->request->data);
		$u = $this->Model->Users->fetch($id);
		if($this->request->is('post')) {
			$u->copyfrom('POST');

			//Handle avatar upload
			if(isset($_FILES['avatar']) && isset($_FILES['avatar']['tmp_name']) && !empty($_FILES['avatar']['tmp_name'])) {			
				$allowedTypes = array(IMAGETYPE_PNG, IMAGETYPE_JPEG);
				$detectedType = exif_imagetype($_FILES['avatar']['tmp_name']);
				if ((!in_array($detectedType, $allowedTypes)) || $_FILES['avatar']['size'] > (2 * 1024 * 1024) || !getimagesize($_FILES['avatar']['tmp_name'])) {
					\StatusMessage::add('Invalid image','danger');
					return $f3->reroute('/user/profile');
				}
				$ext = end((explode(".", $_FILES['avatar']['name'])));
				$_FILES['avatar']['name'] = "avatar_of_user_".$id.".".$ext;
				$url = File::Upload($_FILES['avatar']);
				$u->avatar = $url;
			} else if(isset($reset)) {
				$u->avatar = '';
			}

			$u->save();
			\StatusMessage::add('Profile updated succesfully','success');
			return $f3->reroute('/user/profile');
		}			
		$_POST = $u->cast();
		$f3->set('u',$u);
	}

/*
	// application logic disabled this function
	public function promote($f3) {
		$id = $this->Auth->user('id');
		$u = $this->Model->Users->fetch($id);
		$u->level = 2;
		$u->save();
		return $f3->reroute('/');
	}
*/

}
?>
