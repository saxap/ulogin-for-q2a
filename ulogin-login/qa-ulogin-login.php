<?php

/* Description will be here =) */

class qa_ulogin_login {

	var $size = 'buttons'; // also available sizes is: small, panel, window, but not customizible
	var $providers = 'vkontakte,facebook,twitter,googleplus,yandex,odnoklassniki,mailru'; // available services is: vkontakte,facebook,twitter,googleplus,yandex,odnoklassniki,mailru,livejournal,google,openid,lastfm,linkedin,liveid,soundcloud,steam,flickr,vimeo,youtube,webmoney,foursquare,tumblr,dudu
	var $return_url = 'http%3A%2F%2Fsenator064.com%2Fquestions%2F';//index.php?qa=login
	var $hidden = ''; // available values is: other, any values of $providers with ',' or nothing
	var $realurl = 'http://senator064.com/questions/';

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

				//TODO userIp checking
				//	$userip = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : $_SERVER['REMOTE_ADDR'];

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
						//print_r($userfields);
						//die();

						// If user set remember option
						//if (isset($_GET["remember"]))
							
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
					// 1 yer days cookie
					$expire = time() + 8760 * 60 * 60;
					$expire2 = time() + 8760 * 60 * 60;
					setcookie('qa_ulogin_id',  $uid, $expire);
					setcookie('qa_ulogin_scr', $secret, $expire2);
				}
			}
		}	

	function match_source($source) {

			return $source=='ulogin';

	}

	function login_html($tourl, $context) {
?>
			<script src="//ulogin.ru/js/ulogin.js"></script>
			<style>
				#uLogin img {
					cursor: pointer;
					margin-left: 5px;
				}
			</style>
			<div id="uLogin" x-ulogin-params="display=<?php echo $this->size; ?>;fields=first_name,last_name;optional=email,nickname,bdate,photo;providers=<?php echo $this->providers; ?>;hidden=<?php echo $this->hidden; ?>;redirect_uri=<?php echo $this->return_url; ?>;receiver=<?php echo $this->realurl; ?><?php echo substr($this->urltoroot, 2); ?>xd_custom.html"><!--;callback=ulogin_ucall(<?php // echo $_POST['token']; ?>)-->
				<?php
				$providers_arr = explode(',', $this->providers);
				foreach ($providers_arr as $provider) {
					echo '<img src="'.$this->urltoroot.'buttons/'.$provider.'.png" x-ulogin-button = "'.$provider.'" />';
	}
?>
			</div>

<?php

			
			
		}

	function logout_html($tourl)
		{
?>
			<script type="text/javascript">
				function DeluloginCookies() {
					//alert("is Work");
					document.cookie = 'qa_ulogin_id=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
					document.cookie = 'qa_ulogin_scr=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
					document.location = '<?php echo $this->realurl; ?>index.php?qa=logout';
				}
			</script>

			<a href="#" onclick="DeluloginCookies()">Выйти </a>
			
<?php
		}

}