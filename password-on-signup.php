<?php
/*
 * Plugin Name: Password at registration
 * Plugin URI: trepmal.com
 * Description: Allow user to set password at registration/signup. NOTE: Doesn't change the confirmation screen or email
 * Version: 0.0.1
 * Author: Kailey Lampert
 * Author URI: kaileylampert.com
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * TextDomain: pwd-at-reg
 * DomainPath:
 * Network:
 */

namespace trepmal\Password_At_Registration;

/**
 * Get hooked in
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	// form and errors
	add_action( 'register_form',             $n( 'password_fields' ) );    // single
	add_action( 'signup_extra_fields',       $n( 'password_fields' ) );    // multi
	add_action( 'signup_extra_fields',       $n( 'password_errors' ), 8 ); // multi

	// verification
	add_filter( 'registration_errors',       $n( 'check_password' ), 10, 3 ); // single
	add_filter( 'wpmu_validate_user_signup', $n( 'check_password' ) );        // multi

	// application
	add_action( 'user_register',             $n( 'update_user' ) );                     // single
	add_filter( 'add_signup_meta',           $n( 'save_in_signup_meta' )  );            // multi
	add_action( 'wpmu_activate_user',        $n( 'apply_during_activation' ) , 10, 3 ); // multi

}
setup();

/**
 * Output password field html
 */
function password_fields( ) {
	$form  = '<p>'. esc_html( __( 'You can set your password here. If left blank, a random one will be generated for you.', 'pwd-at-reg' ) ) .'</p>';
	$form .= '<p><label>'. esc_html( __( 'Password:', 'pwd-at-reg' ) ) .'<br /><input type="password" class="input" name="password1" /></label></p>';
	$form .= '<p><label>'. esc_html( __( 'Password again:', 'pwd-at-reg' ) ) .'<br /><input type="password" class="input" name="password2" /></label></p>';
	$form = apply_filters( 'pwd_at_reg_password_field', $form );
	echo $form;
}

/**
 * Fields in multisite registration
 * Output password errors
 */
function password_errors( $errors ) {
	if ( $errmsg = $errors->get_error_message('password_mismatch') ) {
		echo '<p class="error">'.$errmsg.'</p>';
	}
	if ( $errmsg = $errors->get_error_message('password_short') ) {
		echo '<p class="error">'.$errmsg.'</p>';
	}
}

/**
 * Check for password errors
 */
function check_password( $errors ) {

	$password1 = $_POST['password1'];
	$password2 = $_POST['password2'];
	// don't require a password (empty == empty)
	if ( $password1 != $password2  ) {
		$msg = __( 'Passwords do not match', 'pwd-at-reg' );
		if ( is_wp_error( $errors ) ) {
			// single-site, registration_errors callback
			$errors->add('password_mismatch', $msg );
		} else {
			// multisite, wpmu_validate_user_signup callback
			$errors['errors']->add('password_mismatch', $msg );
		}
	}
	// if something is set, require min char count
	if ( strlen( $password1 ) > 0 && strlen( $password1 ) < apply_filters( 'pwd_at_reg_min_length', 8 ) ) {
		$msg = __( 'Password is too short', 'pwd-at-reg');
		if ( is_wp_error( $errors ) ) {
			$errors->add('password_short', $msg );
		} else {
			$errors['errors']->add('password_short', $msg );
		}
	}
	return $errors;

}

/**
 * Update user password
 */
function update_user( $user_id ) {
	// only sets a password if fields not empty
	if ( isset( $_POST['password1'] ) && ! empty( $_POST['password2'] ) )	{
		wp_set_password( $_POST['password1'], $user_id );
	}
}

/**
 * In multisite, need to stash password in meta and set during activation
 */
function save_in_signup_meta( $meta ) {
	$meta['password'] = $_POST['password1'];
	return $meta;
}

/**
 * In multisite, Set password from meta
 */
function apply_during_activation( $user_id, $password, $meta ) {
	//if password was provided, use that
	if ( strlen( $meta['password'] ) > 0 ) {
		wp_set_password( $meta['password'], $user_id );
	}
}
