<?php
///////////////////Description://///////////////
// Question2Answer uLogin Plugin
// Allows users login from uLogin - OpenID Service, uses social accounts.
//////////////////Capabilities://///////////////
//1) Customized appearance and possibility to display your own pictures of buttons
//2) Most popular social networks
////////////////////Install:////////////////////
//1) Upload ulogin-login folder to qa-plugin dir
//2) Change settings in qa-ulogin-login.php file.
/////////How to display my social buttons?//////
//Upload and replace pictures in "/buttons" folder and set $size = 'buttons' in qa-ulogin-login.php file.
/////////////////////Links://///////////////////
//github: https://github.com/saxap/ulogin-for-q2a
//Plugin URL: http://dontforget.pro/php/plagin-avtorizatsii-cherez-ulogin-dlya-question2answer-cms/
/////////////////////Autor://///////////////////
//nick: saxap
//blog: dontforget.pro
//email: saxap@bk.ru


class qa_ulogin_login {
////////////////////////////////////
/////////////SETINGS:///////////////
////////////////////////////////////

	/* How to display buttons */
	var $type = 'buttons'; // available types is: small, panel, window, but not customizible
	/* Providers that will be displayed */	
	var $providers = 'vkontakte,facebook,twitter,googleplus,yandex'; // available services is: vkontakte,facebook,twitter,googleplus,yandex,odnoklassniki,mailru,livejournal,google,openid,lastfm,linkedin,liveid,soundcloud,steam,flickr,vimeo,youtube,webmoney,foursquare,tumblr,dudu
	/* Providers that will be hidden */
	var $hidden = 'other'; // available values is: other, any values of $providers or nothing. only for not buttons
	/* Real URL to q2a */
	var $realurl = 'http://yoursite.ru/'; // where your q2a located
	/* How long remember users */
	var $cookielife = '48'; // cookie lifetime in hours
	/* Additional styles */
	var $style = '<style> #uLogin img { cursor: pointer; margin-left: 5px; } #uLogin { float: left; } .qa-ulogin { float: left; }</style>';
	/* Texts */
	var $exit_text = 'Logout';
	var $login_text = 'Login with..';
	
///////////////////////////////////
///////////SETINGS END/////////////
///////////////////////////////////
	var $urltoroot;

	function load_module($directory, $urltoroot) {
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}

	function get_userfields($data) {
		
			$fields = null;

			if (array_key_exists('first_name', $data)) {
			$fields['handle'] = $data['first_name'];
			}
			if (array_key_exists('last_name', $data)) {
			$fields['handle'] .= ' '.$data['last_name'];
			}
			if (array_key_exists('email', $data)) {
			$fields['email'] = $data['email'];
			}
			if (array_key_exists('photo', $data)) {
			$fields['avatar'] = strlen($data['photo']) ? file_get_contents($data['photo']) : null;
			}
			if (array_key_exists('website', $data)) {
			$fields['website'] = $data['profile'];
			}
			$fields['confirmed'] = true;


			return $fields;

		}





	function check_login() {

			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			require_once QA_INCLUDE_DIR.'qa-db.php';

			$gologin = false; // login?
			$userdata = null;
			$identity = '';
			$setcookie = false;
			$userfields = null;


			// if cookies is set
			if (isset($_COOKIE["qa_ulogin_id"]) && isset($_COOKIE["qa_ulogin_scr"]))
			{
				$uid = $_COOKIE['qa_ulogin_id'];
				$cook = $_COOKIE['qa_ulogin_scr'];

				$useraccount = qa_db_select_with_pending(qa_db_user_account_selectspec($uid, true));
				$secret = $useraccount['passcheck'];
				$lastip = $useraccount['loginip'];
				if (!strcmp($secret, $cook))
				{
					// Get identity from db
					$sub = qa_db_read_all_values(qa_db_query_sub('SELECT identifier FROM ^userlogins WHERE userid=$',$uid));
					$identity = $sub[0];
					$gologin = true;
					$setcookie = true;
				}
			}

			// if login throwout uLogin
			if (isset($_POST['token'])) {

				$rawuser = qa_retrieve_url('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);

				if (strlen($rawuser)) {	
					$user = json_decode($rawuser, true);
					if (is_array($user))
					{
						$gologin = true;
						$userdata = $user;
						$identity = $userdata['identity'];
						$setcookie = true;

						//TODO add userdata convert to userfields

						$userfields = $this->get_userfields($userdata);
							
					}
				}
			}
			
			if ($gologin) {

				qa_log_in_external_user('ulogin', $identity, $userfields);

				// This code, if user sucses loged in

				$secret = '';
				$uid = qa_get_logged_in_userid();


				// When external user login, Q2A not set passcheck for him. Do  it
				if (!qa_get_logged_in_user_field('passsalt') || !qa_get_logged_in_user_field('passcheck')) {

					$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
					$randompassword = $chars{rand(0, 15)};
					// Generate random string
					for ($i = 1; $i < $length; $i = strlen($randompassword))
					{
						// Grab a random character from our list
						$r = $chars{rand(0, $chars_length)};
						// Make sure the same two characters don't appear next to each other
						if ($r != $randompassword{$i - 1}) $randompassword .=  $r;
					}
					//	set password
					qa_db_user_set_password($uid, $randompassword);
				}

				$useraccount = qa_db_select_with_pending(qa_db_user_account_selectspec($uid, true));
				$secret = $useraccount['passcheck'];

				if ($setcookie)
				{
					// cookie
					$expire = time() + $this->cookielife * 60 * 60;
					$expire2 = time() + $this->cookielife * 60 * 60;
					setcookie('qa_ulogin_id',  $uid, $expire);
					setcookie('qa_ulogin_scr', $secret, $expire2);
				}
			}
		}	

	function match_source($source) {

			return $source=='ulogin';

	}

	function login_html($tourl, $context) {
		if ($this->type == 'buttons') {
?>
			<script src="//ulogin.ru/js/ulogin.js"></script>
			<?php echo $this->style; ?>
			<label class="qa-ulogin"><?php echo $this->login_text; ?></label>
			<div id="uLogin" x-ulogin-params="display=<?php echo $this->type; ?>;fields=first_name,last_name;optional=email,nickname,bdate,photo;providers=<?php echo $this->providers; ?>;hidden=<?php echo $this->hidden; ?>;redirect_uri=<?php echo $this->realurl; ?>;receiver=<?php echo $this->realurl; ?><?php echo substr($this->urltoroot, 2); ?>xd_custom.html">
			<?php
				$providers_arr = explode(',', $this->providers);
				foreach ($providers_arr as $provider) {
					echo '<img src="'.$this->urltoroot.'buttons/'.$provider.'.png" x-ulogin-button = "'.$provider.'" />';
				}
?>
			</div>			

<?php  

			
			
		} else { 
?>			
		<script src="//ulogin.ru/js/ulogin.js"></script>
		<?php echo $this->style; ?>
		<label class="qa-ulogin"><?php echo $this->login_text; ?></label>
		<div id="uLogin" data-ulogin="display=<?php echo $this->type; ?>;fields=first_name,last_name;optional=email,nickname,bdate,photo;providers=<?php echo $this->providers; ?>;hidden=<?php echo $this->hidden; ?>;redirect_uri=<?php echo $this->realurl; ?>"></div>

<?php		
		}
	}		

	function logout_html($tourl)
		{
?>
			<script type="text/javascript">
				function DeluloginCookies() {
					document.cookie = 'qa_ulogin_id=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
					document.cookie = 'qa_ulogin_scr=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
					document.location = '<?php echo $this->realurl; ?>index.php?qa=logout';
				}
			</script>

			<a href="#" onclick="DeluloginCookies()"><?php echo $this->exit_text; ?> </a>
			
<?php
		}

}
