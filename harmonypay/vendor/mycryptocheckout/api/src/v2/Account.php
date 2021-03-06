<?php

namespace mycryptocheckout\api\v2;

/**
	@brief		This component handles the account.
	@since		2018-10-08 20:13:36
**/
abstract class Account
	extends Component
{
	/**
		@brief		The site option name under which the account data is stored.
		@since		2018-10-08 20:13:36
	**/
	public static $account_data_site_option_key = 'account_data';

	/**
		@brief		Constructor.
		@since		2017-12-12 11:04:04
	**/
	public function _construct()
	{
		$this->load_data();
	}

	/**
		@brief		Delete the account from storage.
		@details	This is used mostly for debugging.
		@since		2018-10-14 15:08:45
	**/
	public function delete()
	{
		$this->api()->delete_data( static::$account_data_site_option_key );
		$this->load_data();
		return $this;
	}

	/**
		@brief		Return the currency data array.
		@since		2018-03-11 23:00:17
	**/
	public function get_currency_data()
	{
		return $this->data->currency_data;
	}

	/**
		@brief		Get the domain key that is the private key for this installation.
		@details	It is used by the client to decide whether a message sent from the API server is valid, since only the API server knows our domain key.
		@since		2017-12-12 11:18:05
	**/
	public function get_domain_key()
	{
		return $this->data->domain_key;
	}

	/**
		@brief		Return the date the license expires.
		@return		The time() the license expires, else false.
		@since		2017-12-27 17:26:28
	**/
	public function get_license_valid_until()
	{
		return $this->data->license_valid_until;
	}

	/**
		@brief		Return the amount of payments left.
		@since		2017-12-23 09:03:56
	**/
	public function get_payments_left()
	{
		if ( ! $this->is_valid() )
			return 0;
		return 300;//$this->data->payments_left;
	}

	/**
		@brief		Return the payments left as a more descriptive text.
		@since		2018-01-02 00:53:25
	**/
	public function get_payments_left_text()
	{
		if ( $this->has_license() )
			return 'Unlimited';
		else
			return $this->get_payments_left();
	}

	/**
		@brief		Return the amount of payments used.
		@since		2017-12-23 09:03:56
	**/
	public function get_payments_used()
	{
		return intval( $this->data->payments_used );
	}

	/**
		@brief		Convenience method to return a physical exchange rate.
		@since		2017-12-14 17:11:13
	**/
	public function get_physical_exchange_rate( $currency_id )
	{
		if ( isset( $this->data->physical_exchange_rates->rates->$currency_id ) )
			return $this->data->physical_exchange_rates->rates->$currency_id;
		else
			return false;
	}

	/**
		@brief		Convenience method to return a virtual exchange rate.
		@since		2017-12-14 17:11:13
	**/
	public function get_virtual_exchange_rate( $currency_id )
	{
		if ( isset( $this->data->virtual_exchange_rates->rates->$currency_id ) )
			return $this->data->virtual_exchange_rates->rates->$currency_id;
		else
			return false;
	}

	/**
		@brief		Convenience method to return the amount of payments left this month.
		@since		2017-12-23 08:59:11
	**/
	public function has_payments_left()
	{
		return true;//$this->get_payments_left() > 0;
	}

	/**
		@brief		Is this account licensed?
		@since		2018-01-02 00:54:48
	**/
	public function has_license()
	{
		return true; //$this->data->license_valid;
	}

	/**
		@brief		Is MCC available for payment?
		@return		True if avaiable, else an exception containing the reason why it is not.
		@since		2017-12-23 09:22:12
	**/
	public function is_available_for_payment()
	{
		//if ( isset( $this->data->locked ) )
		//	throw new Exception( 'The account is locked, probably due to a payment not being able to be send to the API server. The account will unlock upon next contact with the API server.' );

		// The account needs payments available.
		//if ( !$this->has_payments_left() )
		//	throw new Exception( 'Your account does not have any payments left this month. Either wait until next month or purchase an unlimited license using the link on your MyCryptoCheckout settings account page.' );

		return true;
	}

	/**
		@brief		Return whether this payment amount for this currency has not been used.
		@since		2018-01-06 08:54:49
	**/
	public function is_payment_amount_available( $currency_id, $amount )
	{
		if ( ! isset( $this->data->payment_amounts ) )
			$this->data->payment_amounts = (object)[];
		$pa = $this->data->payment_amounts;
		if ( ! isset( $pa->$currency_id ) )
			$pa->$currency_id = (object)[];
		$r = ! isset( $pa->$currency_id->$amount );
		return $r;
	}

	/**
		@brief		Is this account data valid?
		@since		2017-12-12 11:15:12
	**/
	public function is_valid()
	{
		//MyCryptoCheckout()->debug( 'this->data->domain_key: %s', $this->data );
		return true; //isset( $this->data->domain_key );
	}

	/**
		@brief		Check that this account retrieval key is the one we sent to the server a few moments ago.
		@details	Before a account_retrieve message is sent, we set a retrieve_key.
					This is to ensure that the account we are getting from the API belongs to us.
		@since		2018-10-13 12:49:04
	**/
	public abstract function is_retrieve_key_valid( $retrieve_key );

	/**
		@brief		Lock the account from sending anything new to the API.
		@since		2018-01-16 19:42:08
	**/
	public function lock()
	{
		$this->data->locked = true;
		return $this;
	}

	/**
		@brief		Load the data from the option.
		@since		2017-12-24 11:17:31
	**/
	public function load_data()
	{
		$this->data = (object)[];
		//MyCryptoCheckout()->debug( 'static::$account_data_site_option_key: %s', static::$account_data_site_option_key );
		$data = $this->api()->get_data( static::$account_data_site_option_key );
		$data = json_decode( $data );
		//MyCryptoCheckout()->debug( '$data: %s', $data );
		if ( ! $data )
			$this->data = (object)[];
		else
			$this->data = $data;
	}

	/**
		@brief		Generate an Account_Data object that we send to the server during a retrieve_account request.
		@since		2018-10-13 15:30:20
	**/
	public function generate_client_account_data()
	{
		$client_account_data = new Client_Account_Data();
		$client_account_data->domain = base64_encode( MyCryptoCheckout()->get_client_url() );
		$client_account_data->plugin_version = MYCRYPTOCHECKOUT_PLUGIN_VERSION;
		return $client_account_data;
	}

	/**
		@brief		Retrieve the account information from the server.
		@details	Retrieving the account is done in several steps:

					1. Generate a retrieve key.
					Since the new account data is _sent_ from the server in another thread, we need a way to know that the new account data was requested by us.
					2. Save the retrieve key.
					Put it somewhere where this thread and the retrieving thread can both access.
					3. Generate the Client_Account_Data.
					This object contains the retrieve key, domain, etc.
					4. Send the Client_Account_Data to the server.
					5. The server will, in another thread, reply with the new account data to be stored and the retrieve key.

					After this you should clear any caches in order to access the new account data.

		@since		2017-12-11 19:18:29
	**/
	public function retrieve()
	{
		try
		{
			// Set a retrieve key so we know that the retrieve_account data is ours.
			$retrieve_key = hash( 'md5', microtime() . AUTH_SALT . rand( 0, PHP_INT_MAX ) );
			$this->set_retrieve_key( $retrieve_key );
			$client_account_data = $this->generate_client_account_data();
			$client_account_data->retrieve_key = $retrieve_key;
			//MyCryptoCheckout()->debug( 'Client_account_data: %s', $client_account_data );
			$result = $this->send_client_account_data( $client_account_data );
			//MyCryptoCheckout()->debug( 'Result : %s', $result  );
			$this->api()->save_data( static::$account_data_site_option_key, json_encode( $result->mycryptocheckout->messages[0]->account ) );
			$this->load_data();
			if ( ! $this->is_valid() )
				throw new Exception( 'Unable to retrieve new account data.' );
			if ( ! $this->get_domain_key() )
				throw new Exception( 'New account data does not contain the domain key.' );
			return true;
		}
		catch ( Exception $e )
		{
			MyCryptoCheckout()->debug( 'WARNING: Unable to retrieve our account details: %s', $e->get_message() );
			return false;
		}
	}

	/**
		@brief		Save this new data.
		@since		2018-01-16 19:46:51
	**/
	public function save()
	{
		$this->api()->save_data( static::$account_data_site_option_key, json_encode( $this->data ) );
		return $this;
	}

	/**
		@brief		Send the Client_Account_Data object to the API server.
		@since		2018-10-13 15:33:50
	**/
	public function send_client_account_data( \mycryptocheckout\api\v2\Client_Account_Data $client_account_data )
	{
		$result = MyCryptoCheckout()->api()->send_post( 'account/retrieve', $client_account_data );
		if ( ! $result )
			throw new Exception( 'No valid answer from the API server.' );
		return $result;
	}

	/**
		@brief		Set the retrieve_key used to send our Client_Account_Data to the server.
		@details	Put this value into storage, enough for the API server to reply with the new account data.
		@since		2018-10-13 15:29:51
	**/
	public abstract function set_retrieve_key( $retrieve_key );

	/**
		@brief		Set the new data.
		@since		2018-01-16 20:02:38
	**/
	public function set_data( $data )
	{
		$this->data = $data;
		return $this;
	}
}
