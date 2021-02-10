<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Auction {
	
	public $return_data;
	
	// Constructor
	public function __construct()
	{
		$this->EE =& get_instance();
	}
	
	public function summary() 
	{  
		$tagdata = $this->EE->TMPL->tagdata;
		
		// Fetch entry id from tag parameters
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		if( $entry_id === FALSE ) {
			return "";
		}
		
		// Fetch data from database
		$this->EE->db->select( "MAX(bid_amount) as current_bid, 
			COUNT(*) as total_bids" );
		$this->EE->db->where( "entry_id", $entry_id );
		$this->EE->db->group_by( "entry_id" );
		$query = $this->EE->db->get( "auction" );

		if( $query->num_rows() == 0 ) {
			// No bids exist in the table, so we'll return zeros
			$data = array(
				"current_bid" => "0.00",
				"total_bids" => 0
			);
		} else {
			// Fetch the first (and only) result from the SQL query
			$data = $query->row_array();            
		}

		// Construct $variables array for use in parse_variables method
		$variables = array();
		$variables[] = $data;

		return $this->EE->TMPL->parse_variables( $tagdata, $variables );
	} 
	
	public function form() 
	{   
		// Find the entry_id of the auction to add the form for
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		if( $entry_id === FALSE ) {
			return "";
		}
		
		// Build an array to hold the form's hidden fields
		$hidden_fields = array(
			"entry_id" => $entry_id,
			"ACT" => $this->EE->functions->fetch_action_id( 'Auction', 'place_bid' )
		);
		
		// Build an array with the form data
		$form_data = array(
			"id" => $this->EE->TMPL->form_id,
			"class" => $this->EE->TMPL->form_class,
			"hidden_fields" => $hidden_fields
		);

		// Fetch contents of the tag pair, ie, the form contents
		$tagdata = $this->EE->TMPL->tagdata;

		$form = $this->EE->functions->form_declaration($form_data) . 
			$tagdata . "</form>";

		return $form;
	} 
	
	public function place_bid() 
	{
		$entry_id	= $this->EE->input->post("entry_id", TRUE);
		$member_id	= $this->EE->session->userdata('member_id');
		$bid_amount	= $this->EE->input->post("bid_amount", TRUE);
		$bid_date	= $this->EE->localize->now; 
		
		$query = $this->EE->db->query( "SELECT * FROM exp_auction WHERE entry_id = $entry_id ORDER BY bid_amount DESC LIMIT 1");
		$high_bid = $query->row(1)->bid_amount;
		
		$data = array(
			"entry_id"		=> $entry_id,
			"member_id"		=> $member_id,
			"bid_amount"	=> $bid_amount,
			"bid_date"		=> $bid_date
		);
		
		if ($bid_amount > $high_bid) {
		
			$this->EE->db->insert('auction', $data); 
		
			$ret = $this->EE->functions->create_url('auction/index');

			$data = array(
				'title'		=> 'Thanks for your bid',
				'heading'	=> 'Thanks for your bid',
				'content'	=> "Your bid has been successfully placed",
				'link'		=> array($ret, "Back to site")
			);
			$this->EE->output->show_message($data); 	
		}
		
		else {
		
			$ret = $this->EE->functions->create_url('auction/index');

			$data = array(
				'title' => 'Bid is too low',
				'heading' => 'Bid is too low',
				'content' => "Your bid must be greater than $high_bid",
				'link' => array($ret, "Back to site")
			);
			$this->EE->output->show_message($data);
			
		}
	}
	
	public function history() {
    
		// Find the url title of the auction to add the form for
		$url_title = $this->EE->TMPL->fetch_param('url_title');

		// Find the url title of the auction to add the form for
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');

		// Check that one of them is used
		if( $entry_id === FALSE && $url_title === FALSE ) {
			return "";
		}

		// Find list of bids for this auction
		$this->EE->db->select( "*" );
		$this->EE->db->from( "auction" );        
		$this->EE->db->join( "exp_members", 
			"exp_members.member_id = auction.member_id" );
		$this->EE->db->join( "exp_member_data", 
			"exp_member_data.member_id = auction.member_id" );
		if( $entry_id !== FALSE ) {
			// we have the entry_id, so we don't need exp_channel_titles table
			$this->EE->db->where( "auction.entry_id", $entry_id );
		} else {
			// we have the url_title, so we need exp_channel_titles table
			// to find the entry_id
			$this->EE->db->join( "exp_channel_titles", 
				"exp_channel_titles.entry_id = auction.entry_id" );
			$this->EE->db->where( "exp_channel_titles.url_title", $url_title );            
		}
		$this->EE->db->order_by( "bid_date desc" );        
		$query = $this->EE->db->get();
		
		// If no results are found...
		if( $query->num_rows() == 0 ) {
			// ... return the {if no_results} ... {/if} conditional
			return $this->EE->TMPL->no_results;
		}
		
		// Put history data in an array and return the parsed the tag data
		$data = $query->result_array();
		$tagdata = $this->EE->TMPL->tagdata;
		return $this->EE->TMPL->parse_variables( $tagdata, $data );
	}
}
/* End of file mod.auction.php */
/* Location: /system/expressionengine/third_party/auction/mod.auction.php */